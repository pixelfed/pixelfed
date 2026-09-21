<?php

namespace App\Services;

use App\Federation\ActivityBuilders\AccountDeleteActivityBuilder;
use App\Jobs\Federation\DeliverAccountDeleteActivity;
use App\Models\Instance;
use App\Models\Profile;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;
use Closure;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;
use Throwable;

/**
 * Tells the rest of the network that a local account has been deleted.
 *
 * This does not go through ActivityPubDeliveryService on purpose. By the time
 * a deletion is federated the profile is inactive and soft deleted, which
 * validateSender() refuses, and the audience is every known shared inbox
 * rather than a follower list. The activity is serialized and hashed once,
 * and every request is signed against that one digest.
 *
 * Callers:
 *   - FanoutAccountDeleteActivity / DeliverAccountDeleteActivity (queued)
 *   - app:user-account-delete (interactive)
 */
class AccountDeleteFederationService
{
    public const string CONTENT_TYPE = 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"';

    /**
     * Inboxes per DeliverAccountDeleteActivity job. At the default delivery
     * concurrency of 10 and a 10 second timeout the worst case is about 100
     * seconds, well inside the 300 second Horizon worker timeout.
     */
    public const int CHUNK_SIZE = 100;

    private const string ACCEPT = 'application/activity+json, application/ld+json; profile="https://www.w3.org/ns/activitystreams"';

    private const array RETRYABLE_STATUSES = [408, 425, 429, 500, 502, 503, 504];

    private const array DELETED_STATUSES = ['delete', 'deleted'];

    private const int TIMEOUT = 10;

    private const int CONNECT_TIMEOUT = 5;

    private const int AUDIENCE_WINDOW_DAYS = 30;

    private const string FANOUT_LOCK = 'pf:services:account-delete:fanout:';

    private const int FANOUT_LOCK_TTL = 43200;

    public function __construct(protected AccountDeleteActivityBuilder $builder) {}

    /**
     * Whether this profile is in a state where announcing its deletion is
     * correct. Not every path into DeleteAccountPipeline sets the status
     * column, so a soft deleted profile counts too.
     */
    public function isDeleted(Profile $profile): bool
    {
        return $profile->trashed() || in_array($profile->status, self::DELETED_STATUSES, true);
    }

    /**
     * Build, serialize and hash the activity, and prove the profile key can
     * sign it, so a broken key fails here once and not once per inbox.
     *
     * The private key is deliberately not part of the result: it is read off
     * the profile at signing time and never travels through a queue payload.
     *
     * @return array{payload: string, digest: string, length: int, key_id: string}
     *
     * @throws RuntimeException when this profile cannot federate a deletion
     */
    public function prepare(Profile $profile): array
    {
        if ($profile->domain !== null) {
            throw new RuntimeException('Only local accounts can federate their own deletion.');
        }

        if (! $this->isDeleted($profile)) {
            throw new RuntimeException('Refusing to federate a deletion for an account that is not deleted.');
        }

        if (empty($profile->private_key)) {
            throw new RuntimeException('Profile private key has been wiped, cannot sign deletion activity.');
        }

        $keyId = $profile->keyId();

        if (empty($keyId)) {
            throw new RuntimeException('Profile key id is missing, cannot sign deletion activity.');
        }

        try {
            $payload = json_encode(
                $this->builder->build($profile),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            throw new RuntimeException("Failed to encode delete payload: {$e->getMessage()}", 0, $e);
        }

        $prepared = [
            'payload' => $payload,
            'digest' => base64_encode(hash('sha256', $payload, true)),
            'length' => strlen($payload),
            'key_id' => $keyId,
        ];

        try {
            $this->signedHeaders($profile, $prepared, rtrim((string) config('app.url'), '/').'/inbox');
        } catch (Throwable $e) {
            throw new RuntimeException("Unable to sign with this profile's key: {$e->getMessage()}", 0, $e);
        }

        return $prepared;
    }

    /**
     * Every shared inbox of an instance seen recently enough to be worth a
     * request.
     *
     * @return Collection<int, string>
     */
    public function audience(): Collection
    {
        return Instance::query()
            ->whereNotNull('shared_inbox')
            ->whereNotNull('nodeinfo_last_fetched')
            ->where('nodeinfo_last_fetched', '>', now()->subDays(self::AUDIENCE_WINDOW_DAYS))
            ->toBase()
            ->distinct()
            ->orderBy('shared_inbox')
            ->pluck('shared_inbox')
            ->filter(fn ($inbox): bool => is_string($inbox) && trim($inbox) !== '')
            ->unique()
            ->values();
    }

    /**
     * Queue the deletion for delivery to the whole audience.
     *
     * Guarded by a lock so two runs of the delete pipeline for the same
     * account (a double click, a retried job) broadcast once. A deliberate
     * resend goes through app:user-account-delete, which does not take it.
     *
     * @return int Number of inboxes queued, 0 when a fanout already ran
     *
     * @throws RuntimeException when this profile cannot federate a deletion
     */
    public function fanout(Profile $profile, int $chunkSize = self::CHUNK_SIZE): int
    {
        $this->prepare($profile);

        $lock = self::FANOUT_LOCK.$profile->id;

        if (! Cache::add($lock, 1, self::FANOUT_LOCK_TTL)) {
            return 0;
        }

        try {
            $audience = $this->audience();

            foreach ($audience->chunk(max(1, $chunkSize)) as $chunk) {
                DeliverAccountDeleteActivity::dispatch($profile->id, $chunk->values()->all())
                    ->onQueue('delete');
            }
        } catch (Throwable $e) {
            // A Delete that arrives twice is harmless, one that never
            // arrives is not, so let the retry start over.
            Cache::forget($lock);

            throw $e;
        }

        return $audience->count();
    }

    /**
     * One pooled pass over a set of inboxes. Nothing is retried here: the
     * caller decides what to do with `retryable`, since a queued job and an
     * interactive command want very different waits.
     *
     * Every inbox handed in lands in exactly one bucket, keyed by the url
     * as it was passed:
     *
     *   delivered    2xx
     *   skipped      host currently marked unavailable by DeliveryHostService
     *   invalid      failed url validation (private ip, banned, malformed) or signing
     *   http_failed  answered with a status that another attempt will not fix
     *   retryable    no answer, or one of RETRYABLE_STATUSES
     *
     * @param  array{payload: string, digest: string, length: int, key_id: string}  $prepared
     * @param  array<int, string>  $inboxes
     * @param  Closure|null  $onFailure  fn (string $url, string $reason, ?int $status): void
     * @return array{delivered: array<string, int>, skipped: array<int, string>, invalid: array<string, string>, http_failed: array<string, array{status: int, body: string}>, retryable: array<string, string>}
     */
    public function deliver(
        Profile $profile,
        array $prepared,
        array $inboxes,
        ?int $concurrency = null,
        ?Closure $onFailure = null
    ): array {
        $result = [
            'delivered' => [],
            'skipped' => [],
            'invalid' => [],
            'http_failed' => [],
            'retryable' => [],
        ];

        $inboxes = array_values(array_unique(array_filter(
            $inboxes,
            fn ($inbox): bool => is_string($inbox) && trim($inbox) !== ''
        )));

        if ($inboxes === []) {
            return $result;
        }

        $reachable = array_values(DeliveryHostService::filter($inboxes));

        $result['skipped'] = array_values(array_diff($inboxes, $reachable));

        // Validate and sign everything before the pool starts, so one bad
        // row cannot take the rest of the batch down with it.
        $requests = [];

        foreach ($reachable as $inbox) {
            $destination = Helpers::validateUrlWithReason($inbox);

            if (! is_string($destination['url']) || $destination['url'] === '') {
                $result['invalid'][$inbox] = $destination['reason'];

                continue;
            }

            try {
                $requests[$inbox] = [
                    'url' => $destination['url'],
                    'headers' => $this->signedHeaders($profile, $prepared, $destination['url']),
                ];
            } catch (Throwable $e) {
                $result['invalid'][$inbox] = $e->getMessage();
            }
        }

        if ($requests === []) {
            return $result;
        }

        $payload = $prepared['payload'];

        $responses = Http::pool(
            function (Pool $pool) use ($requests, $payload) {
                foreach ($requests as $inbox => $request) {
                    // Http::pool requests inherit nothing, so the signed
                    // User-Agent and Accept have to be set on each one or
                    // Guzzle sends its own and the signature stops matching.
                    $pool->as($inbox)
                        ->timeout(self::TIMEOUT)
                        ->connectTimeout(self::CONNECT_TIMEOUT)
                        ->withOptions(['allow_redirects' => false])
                        ->withHeaders($request['headers'])
                        ->withBody($payload, self::CONTENT_TYPE)
                        ->post($request['url']);
                }
            },
            max(1, $concurrency ?? self::concurrency())
        );

        $hostFailures = [];
        $hostSuccesses = [];

        foreach ($requests as $inbox => $request) {
            $response = $responses[$inbox] ?? null;
            $domain = DeliveryHostService::domain($request['url']);

            if (! $response instanceof Response) {
                $reason = $response instanceof Throwable ? $response->getMessage() : 'No response';

                $result['retryable'][$inbox] = $reason;

                if ($domain) {
                    $hostFailures[$domain] = true;
                }

                if ($onFailure) {
                    $onFailure($inbox, $reason, null);
                }

                continue;
            }

            $status = $response->status();

            if ($domain) {
                // 5xx is a broken host. Anything else means it answered,
                // even if it did not like the request.
                if ($response->serverError()) {
                    $hostFailures[$domain] = true;
                } else {
                    $hostSuccesses[$domain] = true;
                }
            }

            if ($response->successful()) {
                $result['delivered'][$inbox] = $status;

                continue;
            }

            $body = mb_substr((string) $response->body(), 0, 500);

            if ($onFailure) {
                $onFailure($inbox, $body, $status);
            }

            if (self::isRetryableStatus($status)) {
                $result['retryable'][$inbox] = "HTTP {$status}";

                continue;
            }

            $result['http_failed'][$inbox] = [
                'status' => $status,
                'body' => $body,
            ];
        }

        foreach (array_keys($hostSuccesses) as $domain) {
            unset($hostFailures[$domain]);
        }

        DeliveryHostService::recordFailures(array_keys($hostFailures));
        DeliveryHostService::recordSuccesses(array_keys($hostSuccesses));

        return $result;
    }

    /**
     * Send to exactly one inbox and hand back the raw response. This is the
     * debugging path: the url is used as given, with no validation and no
     * host health, so it can be pointed at anything.
     *
     * Pass $headers to send exactly the set signedHeaders() returned earlier,
     * so what was printed is what went over the wire.
     *
     * @param  array{payload: string, digest: string, length: int, key_id: string}  $prepared
     * @param  array<string, string>|null  $headers
     */
    public function send(
        Profile $profile,
        array $prepared,
        string $url,
        int $timeout = self::TIMEOUT,
        ?array $headers = null
    ): Response {
        return Http::timeout(max(1, $timeout))
            ->connectTimeout(self::CONNECT_TIMEOUT)
            ->withOptions(['allow_redirects' => false])
            ->withHeaders($headers ?? $this->signedHeaders($profile, $prepared, $url))
            ->withBody($prepared['payload'], self::CONTENT_TYPE)
            ->post($url);
    }

    /**
     * @param  array{payload: string, digest: string, length: int, key_id: string}  $prepared
     * @return array<string, string>
     *
     * @throws RuntimeException
     */
    public function signedHeaders(Profile $profile, array $prepared, string $url): array
    {
        $headers = HttpSignature::signRawWithDigest(
            (string) $profile->private_key,
            $prepared['key_id'],
            $url,
            $prepared['digest'],
            [
                'User-Agent' => 'Pixelfed ('.config('app.url').')',
                'Accept' => self::ACCEPT,
            ]
        );

        if (! isset($headers['Signature'])) {
            throw new RuntimeException('Signing produced no Signature header.');
        }

        $headers['Content-Type'] = self::CONTENT_TYPE;
        $headers['Content-Length'] = (string) $prepared['length'];

        return $headers;
    }

    public static function isRetryableStatus(int $status): bool
    {
        return in_array($status, self::RETRYABLE_STATUSES, true);
    }

    private static function concurrency(): int
    {
        return max(
            1,
            (int) config('federation.activitypub.delivery.concurrency', 10)
        );
    }
}
