<?php

namespace App\Services;

use App\Exceptions\InvalidDeliveryDestinationException;
use App\Models\Profile;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool as HttpPool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Throwable;

class ActivityPubDeliveryService
{
    private const CONTENT_TYPE = 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"';

    public ?Profile $sender = null;

    public ?string $to = null;

    public mixed $payload = null;

    public static function queue(): static
    {
        return new static;
    }

    public function from(Profile $profile): static
    {
        $this->sender = $profile;

        return $this;
    }

    public function to(string $url): static
    {
        $this->to = $url;

        return $this;
    }

    public function payload(mixed $payload): static
    {
        $this->payload = $payload;

        return $this;
    }

    public function send(): void
    {
        $this->queueDelivery();
    }

    /**
     * Deliver a single ActivityPub activity.
     *
     * @throws InvalidDeliveryDestinationException when the inbox URL fails validation
     */
    protected function queueDelivery(): void
    {
        if (! $this->sender) {
            throw new InvalidArgumentException('Missing ActivityPub sender.');
        }

        if (! $this->to) {
            throw new InvalidArgumentException('Missing ActivityPub destination.');
        }

        if ($this->payload === null) {
            throw new InvalidArgumentException('Missing ActivityPub payload.');
        }

        self::validateSender($this->sender);

        $domain = DeliveryHostService::domain($this->to);

        $url = self::validateDestination($this->to);

        if (! $url) {
            if ($domain) {
                DeliveryHostService::recordFailure($domain);
            }

            throw new InvalidDeliveryDestinationException(
                'Invalid ActivityPub destination URL.'
            );
        }

        if (! app()->environment('production')) {
            Log::info('Skipped ActivityPub delivery outside production', [
                'profile_id' => $this->sender->id,
                'url' => $url,
            ]);

            return;
        }

        if ($domain && DeliveryHostService::isUnavailable($domain)) {
            Log::info('Skipped ActivityPub delivery to unavailable host', [
                'profile_id' => $this->sender->id,
                'url' => $url,
            ]);

            return;
        }

        try {
            $payload = self::serializePayload($this->payload);

            $headers = self::signedHeaders(
                $this->sender,
                $url,
                $payload
            );

            $response = self::sendRequest(
                $url,
                $payload,
                $headers
            );

            if ($domain) {
                if ($response->serverError()) {
                    DeliveryHostService::recordFailure($domain);
                } else {
                    DeliveryHostService::recordSuccess($domain);
                }
            }

            if ($response->failed()) {
                self::logFailedResponse(
                    $url,
                    $this->sender,
                    $response
                );
            }
        } catch (Throwable $e) {
            if ($domain && $e instanceof ConnectionException) {
                DeliveryHostService::recordFailure($domain);
            }

            Log::warning('ActivityPub delivery failed', [
                'profile_id' => $this->sender->id,
                'url' => $url,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Deliver an activity to multiple inboxes concurrently.
     *
     * The activity is JSON encoded exactly once. That exact serialized string
     * is both signed and transmitted, ensuring the Digest header matches the
     * bytes received by the remote ActivityPub server.
     *
     * Hosts currently marked unavailable by DeliveryHostService are skipped
     * silently (counted in the result, no $onError call). Connection
     * failures, 5xx responses and inbox URLs that fail validation count
     * against the host; any other response clears its failure count.
     *
     * @param  Profile  $profile  Local sender used for HTTP signatures
     * @param  array<int, string>  $audience  Inbox URLs
     * @param  array<string, mixed>  $activity  ActivityPub activity
     * @param  \Closure|null  $onError  fn(Throwable|Response $reason, int $index): void
     * @return array{total: int, skipped: int, duplicate: int, invalid: int, sent: int, delivered: int, rejected: int, failed: int}
     *
     * @throws JsonException
     */
    public static function pool(
        Profile $profile,
        array $audience,
        array $activity,
        ?\Closure $onError = null
    ): array {
        $result = [
            'total' => count($audience),
            'skipped' => 0,     // host currently marked unavailable
            'duplicate' => 0,   // same inbox URL earlier in this audience
            'invalid' => 0,     // failed validation (dead DNS, banned, malformed)
            'sent' => 0,        // requests actually made
            'delivered' => 0,   // 2xx / 3xx
            'rejected' => 0,    // 4xx / 5xx
            'failed' => 0,      // connection failure or signing error
        ];

        if (empty($audience)) {
            return $result;
        }

        self::validateSender($profile);

        if (! app()->environment('production')) {
            Log::info('Skipped pooled ActivityPub delivery outside production', [
                'profile_id' => $profile->id,
                'destinations' => count($audience),
            ]);

            return $result;
        }

        /*
         * Serialize exactly once.
         *
         * HttpSignature::_digest() accepts raw strings, so this exact payload
         * will be hashed for the Digest header and sent as the request body.
         */
        $payload = self::serializePayload($activity);

        /*
         * Prepare and sign every delivery before starting the HTTP pool.
         *
         * This prevents a signing or validation error for one destination
         * from corrupting the response/index relationship for other requests.
         */
        $deliveries = [];

        $seen = [];

        /*
         * Host health, collected during the batch and applied once at the
         * end so no database writes happen inside the HTTP phase.
         */
        $hostFailures = [];

        $hostSuccesses = [];

        foreach ($audience as $index => $destination) {
            $domain = null;

            try {
                if (! is_string($destination) || trim($destination) === '') {
                    throw new InvalidDeliveryDestinationException(
                        'ActivityPub inbox URL must be a non-empty string.'
                    );
                }

                $domain = DeliveryHostService::domain($destination);

                if ($domain && DeliveryHostService::isUnavailable($domain)) {
                    $result['skipped']++;

                    continue;
                }

                $url = self::validateDestination($destination);

                if (! $url) {
                    throw new InvalidDeliveryDestinationException(
                        'Invalid ActivityPub destination URL.'
                    );
                }

                /*
                 * Avoid delivering the same activity multiple times to the
                 * same inbox when shared inboxes or audience expansion result
                 * in duplicate URLs.
                 */
                if (isset($seen[$url])) {
                    $result['duplicate']++;

                    continue;
                }

                $seen[$url] = true;

                $headers = self::signedHeaders(
                    $profile,
                    $url,
                    $payload
                );

                $deliveries[] = [
                    'index' => $index,
                    'url' => $url,
                    'domain' => DeliveryHostService::domain($url) ?? $domain,
                    'headers' => $headers,
                ];
            } catch (InvalidDeliveryDestinationException $e) {
                /*
                 * Expected churn: dead hosts, banned instances, stale rows.
                 * Counted against the host and reported to the caller, but
                 * not worth a warning per inbox per activity.
                 */
                $result['invalid']++;

                if ($domain) {
                    $hostFailures[$domain] = true;
                }

                Log::debug('Skipped ActivityPub delivery to invalid inbox', [
                    'profile_id' => $profile->id,
                    'index' => $index,
                    'url' => is_string($destination)
                        ? $destination
                        : null,
                    'error' => $e->getMessage(),
                ]);

                if ($onError) {
                    $onError($e, $index);
                }
            } catch (Throwable $e) {
                /*
                 * Anything else here is a signing or serialization problem
                 * on our side and deserves attention.
                 */
                $result['failed']++;

                Log::warning('Unable to prepare ActivityPub delivery', [
                    'profile_id' => $profile->id,
                    'index' => $index,
                    'url' => is_string($destination)
                        ? $destination
                        : null,
                    'exception' => $e::class,
                    'error' => $e->getMessage(),
                ]);

                if ($onError) {
                    $onError($e, $index);
                }
            }
        }

        $result['sent'] = count($deliveries);

        if (! empty($deliveries)) {
            $timeout = self::deliveryTimeout();
            $connectTimeout = self::connectTimeout($timeout);

            /*
             * Each request is named using the original audience index.
             *
             * Laravel's Pool::as() ensures the response can be mapped directly
             * back to that destination even when some audience entries were
             * rejected during validation/signing.
             */
            $responses = Http::pool(
                function (HttpPool $pool) use (
                    $deliveries,
                    $payload,
                    $timeout,
                    $connectTimeout
                ) {
                    foreach ($deliveries as $delivery) {
                        $pool
                            ->as((string) $delivery['index'])
                            ->replaceHeaders($delivery['headers'])
                            ->timeout($timeout)
                            ->connectTimeout($connectTimeout)
                            ->withoutRedirecting()
                            ->send('POST', $delivery['url'], [
                                'body' => $payload,
                            ]);
                    }
                }
            );

            $deliveriesByIndex = [];

            foreach ($deliveries as $delivery) {
                $deliveriesByIndex[(string) $delivery['index']] = $delivery;
            }

            foreach ($responses as $index => $response) {
                $delivery = $deliveriesByIndex[(string) $index] ?? null;

                $url = $delivery['url'] ?? null;

                $domain = $delivery['domain'] ?? null;

                if ($response instanceof Throwable) {
                    $result['failed']++;

                    if ($domain) {
                        $hostFailures[$domain] = true;
                    }

                    Log::info('ActivityPub pooled delivery connection failure', [
                        'profile_id' => $profile->id,
                        'index' => $index,
                        'url' => $url,
                        'exception' => $response::class,
                        'error' => $response->getMessage(),
                    ]);

                    if ($onError) {
                        $onError($response, (int) $index);
                    }

                    continue;
                }

                if (! $response instanceof Response) {
                    $result['failed']++;

                    $exception = new RuntimeException(
                        'Unexpected ActivityPub HTTP pool response type.'
                    );

                    Log::warning('Unexpected ActivityPub pooled delivery response', [
                        'profile_id' => $profile->id,
                        'index' => $index,
                        'url' => $url,
                        'response_type' => get_debug_type($response),
                    ]);

                    if ($onError) {
                        $onError($exception, (int) $index);
                    }

                    continue;
                }

                if ($response->failed()) {
                    $result['rejected']++;

                    if ($domain) {
                        /*
                         * 5xx means the host is broken or dead behind a
                         * proxy. 4xx means it answered, so it is reachable
                         * even if it disliked the request.
                         */
                        if ($response->serverError()) {
                            $hostFailures[$domain] = true;
                        } else {
                            $hostSuccesses[$domain] = true;
                        }
                    }

                    self::logFailedResponse(
                        $url,
                        $profile,
                        $response,
                        (int) $index
                    );

                    if ($onError) {
                        $onError($response, (int) $index);
                    }

                    continue;
                }

                $result['delivered']++;

                if ($domain) {
                    $hostSuccesses[$domain] = true;
                }
            }
        }

        /*
         * A host that answered at all during this batch is reachable, even
         * if another inbox on it failed.
         */
        foreach (array_keys($hostSuccesses) as $domain) {
            unset($hostFailures[$domain]);
        }

        DeliveryHostService::recordFailures(array_keys($hostFailures));

        DeliveryHostService::recordSuccesses(array_keys($hostSuccesses));

        return $result;
    }

    /**
     * Validate that the profile is a local, active profile that may be used
     * to sign outgoing ActivityPub requests.
     */
    private static function validateSender(Profile $profile): void
    {
        if ($profile->domain !== null) {
            throw new InvalidArgumentException(
                'Remote profiles cannot sign outgoing ActivityPub deliveries.'
            );
        }

        if ($profile->status !== null) {
            throw new InvalidArgumentException(
                'Inactive profiles cannot sign outgoing ActivityPub deliveries.'
            );
        }

        if (empty($profile->private_key)) {
            throw new InvalidArgumentException(
                'ActivityPub sender does not have a private key.'
            );
        }
    }

    /**
     * Validate and normalize an ActivityPub inbox URL.
     */
    private static function validateDestination(string $url): string|false
    {
        $url = trim($url);

        if ($url === '') {
            return false;
        }

        /*
         * Helpers::validateUrl() provides Pixelfed's SSRF / hostname /
         * federation URL validation.
         *
         * Use the normalized URL it returns rather than continuing with the
         * caller-provided value.
         */
        $validated = Helpers::validateUrl($url);

        if (! is_string($validated) || $validated === '') {
            return false;
        }

        return $validated;
    }

    /**
     * Serialize an ActivityPub payload exactly once.
     *
     * If a serialized JSON string is explicitly supplied, leave it untouched.
     *
     * @throws JsonException
     */
    private static function serializePayload(mixed $payload): string
    {
        if (is_string($payload)) {
            if ($payload === '') {
                throw new InvalidArgumentException(
                    'ActivityPub payload cannot be empty.'
                );
            }

            /*
             * Ensure caller-provided strings are valid JSON without
             * re-encoding them and changing the bytes that will be signed.
             */
            json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

            return $payload;
        }

        return json_encode(
            $payload,
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * Generate signed HTTP headers for the exact serialized payload.
     *
     * @return array<string, string>
     */
    private static function signedHeaders(
        Profile $profile,
        string $url,
        string $payload
    ): array {
        $headers = HttpSignature::sign(
            $profile,
            $url,
            $payload,
            [
                'Content-Type' => self::CONTENT_TYPE,
                'User-Agent' => self::userAgent(),
            ]
        );

        if (empty($headers)) {
            throw new RuntimeException(
                'Unable to generate ActivityPub HTTP signature.'
            );
        }

        /*
         * HttpSignature::sign() currently returns cURL-style:
         *
         * [
         *     "Host: example.org",
         *     "Date: ...",
         *     "Digest: ...",
         *     "Signature: ...",
         * ]
         */
        $headers = self::parseCurlHeaders($headers);

        self::assertRequiredSignatureHeaders($headers);

        return $headers;
    }

    /**
     * Send a raw, signed ActivityPub POST request.
     */
    private static function sendRequest(
        string $url,
        string $payload,
        array $headers
    ): Response {
        $timeout = self::deliveryTimeout();

        return Http::replaceHeaders($headers)
            ->timeout($timeout)
            ->connectTimeout(self::connectTimeout($timeout))
            ->withoutRedirecting()
            ->send('POST', $url, [
                /*
                 * Do not use post($url, $data) here.
                 *
                 * The body has already been serialized and signed. Sending
                 * the raw body guarantees Guzzle does not JSON-encode or
                 * otherwise transform it after the Digest was generated.
                 */
                'body' => $payload,
            ]);
    }

    /**
     * Ensure the HTTP signature contains the headers required for a signed
     * ActivityPub POST.
     *
     * @param  array<string, string>  $headers
     */
    private static function assertRequiredSignatureHeaders(array $headers): void
    {
        $normalized = array_change_key_case(
            $headers,
            CASE_LOWER
        );

        foreach (
            [
                'host',
                'date',
                'digest',
                'signature',
                'content-type',
            ] as $required
        ) {
            if (
                ! isset($normalized[$required])
                || trim($normalized[$required]) === ''
            ) {
                throw new RuntimeException(
                    "Missing required ActivityPub signature header: {$required}"
                );
            }
        }
    }

    /**
     * Convert cURL-format headers ("Header: value") to an associative array.
     *
     * @param  array<int, string>  $curlHeaders
     * @return array<string, string>
     */
    private static function parseCurlHeaders(array $curlHeaders): array
    {
        $headers = [];

        foreach ($curlHeaders as $header) {
            if (! is_string($header)) {
                continue;
            }

            $position = strpos($header, ':');

            if ($position === false) {
                continue;
            }

            $name = trim(
                substr($header, 0, $position)
            );

            $value = trim(
                substr($header, $position + 1)
            );

            if ($name === '' || $value === '') {
                continue;
            }

            $headers[$name] = $value;
        }

        return $headers;
    }

    private static function userAgent(): string
    {
        $version = config('pixelfed.version');
        $appUrl = config('app.url');

        return "(Pixelfed/{$version}; +{$appUrl})";
    }

    private static function deliveryTimeout(): int
    {
        $timeout = (int) config(
            'federation.activitypub.delivery.timeout',
            30
        );

        return max(1, $timeout);
    }

    private static function connectTimeout(int $timeout): int
    {
        return max(
            1,
            min($timeout, 10)
        );
    }

    private static function logFailedResponse(
        ?string $url,
        Profile $profile,
        Response $response,
        ?int $index = null
    ): void {
        $context = [
            'profile_id' => $profile->id,
            'url' => $url,
            'status' => $response->status(),

            /*
             * Mastodon often returns a useful reason for 400/401/422
             * responses, but cap it so a remote server cannot flood logs.
             */
            'response' => mb_substr(
                $response->body(),
                0,
                2000
            ),
        ];

        if ($index !== null) {
            $context['index'] = $index;
        }

        /*
         * 4xx usually means a signature or compatibility problem worth
         * seeing. 5xx is almost always a broken or dead host and is handled
         * by DeliveryHostService, so keep it out of the warning stream.
         */
        Log::log(
            $response->serverError() ? 'info' : 'warning',
            'ActivityPub delivery rejected by remote server',
            $context
        );
    }
}
