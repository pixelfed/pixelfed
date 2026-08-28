<?php

namespace App\Services;

use App\Profile;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
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

        $ch = curl_init($this->to);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_exec($ch);
    }

    /**
     * Deliver an activity to multiple inboxes concurrently using a Guzzle pool.
     *
     * @param  Profile  $profile  The sender profile (used for HTTP signature)
     * @param  array<int, string>  $audience  List of inbox URLs to deliver to
     * @param  array  $activity  The activity payload (will be JSON-encoded)
     * @param  \Closure|null  $onError  Optional callback for rejected requests: fn($reason, $index) => void
     */
    public static function pool(Profile $profile, array $audience, array $activity, ?\Closure $onError = null): void
    {
        if (empty($audience)) {
            return;
        }

        $payload = json_encode($activity);

        $client = new Client([
            'timeout' => config('federation.activitypub.delivery.timeout'),
        ]);

        $version = config('pixelfed.version');
        $appUrl = config('app.url');
        $userAgent = "(Pixelfed/{$version}; +{$appUrl})";

        $requests = function () use ($client, $audience, $activity, $profile, $payload, $userAgent) {
            foreach ($audience as $url) {
                $headers = HttpSignature::sign($profile, $url, $activity, [
                    'Content-Type' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
                    'User-Agent' => $userAgent,
                ]);
                yield function () use ($client, $url, $headers, $payload) {
                    return $client->postAsync($url, [
                        'curl' => [
                            CURLOPT_HTTPHEADER => $headers,
                            CURLOPT_POSTFIELDS => $payload,
                            CURLOPT_HEADER => true,
                        ],
                    ]);
                };
            }
        };

        $pool = new Pool($client, $requests(), [
            'concurrency' => config('federation.activitypub.delivery.concurrency'),
            'fulfilled' => function ($response, $index) {},
            'rejected' => $onError ?? function ($reason, $index) {},
        ]);

        $pool->promise()->wait();
    }
}
