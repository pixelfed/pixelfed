<?php

namespace App\Services;

use App\Models\Profile;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Http\Client\Pool as HttpPool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ActivityPubDeliveryService
{
    public $sender;

    public $to;

    public $payload;

    public static function queue()
    {
        return new self;
    }

    public function from($profile)
    {
        $this->sender = $profile;

        return $this;
    }

    public function to(string $url)
    {
        $this->to = $url;

        return $this;
    }

    public function payload($payload)
    {
        $this->payload = $payload;

        return $this;
    }

    public function send()
    {
        return $this->queueDelivery();
    }

    protected function queueDelivery()
    {
        abort_if(! $this->sender || ! $this->to || ! $this->payload, 400);
        abort_if(! Helpers::validateUrl($this->to), 400);
        abort_if($this->sender->domain != null || $this->sender->status != null, 400);

        if (config('app.env') !== 'production') {
            Log::info('Skipped delivery to '.$this->to);

            return;
        }
        $body = $this->payload;
        $payload = json_encode($body);
        $version = config('pixelfed.version');
        $appUrl = config('app.url');
        $headers = HttpSignature::sign($this->sender, $this->to, $body, [
            'Content-Type' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
            'User-Agent' => "(Pixelfed/{$version}; +{$appUrl})",
        ]);

        if (empty($headers)) {
            Log::error('ActivityPub delivery failed: could not generate signature headers', [
                'sender_id' => $this->sender->id,
                'destination' => $this->to,
            ]);

            return false;
        }

        $ch = curl_init($this->to);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, config('federation.activitypub.delivery.timeout', 30));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            Log::error('ActivityPub delivery failed: cURL error', [
                'sender_id' => $this->sender->id,
                'destination' => $this->to,
                'error' => $error,
            ]);

            return false;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            Log::warning('ActivityPub delivery received a non-2xx response', [
                'sender_id' => $this->sender->id,
                'destination' => $this->to,
                'http_code' => $httpCode,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Deliver an activity to multiple inboxes concurrently using Laravel's HTTP pool.
     *
     * @param  Profile  $profile  The sender profile (used for HTTP signature)
     * @param  array<int, string>  $audience  List of inbox URLs to deliver to
     * @param  array  $activity  The activity payload (will be JSON-encoded)
     * @param  \Closure|null  $onError  Optional callback for failed requests: fn($reason, $index) => void
     */
    public static function pool(Profile $profile, array $audience, array $activity, ?\Closure $onError = null): void
    {
        if (empty($audience)) {
            return;
        }

        $payload = json_encode($activity);
        $version = config('pixelfed.version');
        $appUrl = config('app.url');
        $userAgent = "(Pixelfed/{$version}; +{$appUrl})";
        $timeout = config('federation.activitypub.delivery.timeout');

        $responses = Http::pool(function (HttpPool $pool) use ($audience, $activity, $profile, $payload, $userAgent, $timeout) {
            foreach ($audience as $url) {
                $curlHeaders = HttpSignature::sign($profile, $url, $activity, [
                    'Content-Type' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
                    'User-Agent' => $userAgent,
                ]);

                $headers = self::parseCurlHeaders($curlHeaders);

                $pool->withHeaders($headers)
                    ->timeout($timeout)
                    ->withBody($payload, 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"')
                    ->post($url);
            }
        });

        if ($onError) {
            foreach ($responses as $index => $response) {
                if ($response instanceof \Throwable || (method_exists($response, 'failed') && $response->failed())) {
                    $onError($response, $index);
                }
            }
        }
    }

    /**
     * Convert curl-format header array ("Header: value") to associative array.
     *
     * @param  array<int, string>  $curlHeaders
     * @return array<string, string>
     */
    private static function parseCurlHeaders(array $curlHeaders): array
    {
        $headers = [];
        foreach ($curlHeaders as $header) {
            $parts = explode(': ', $header, 2);
            if (count($parts) === 2) {
                $headers[$parts[0]] = $parts[1];
            }
        }

        return $headers;
    }
}
