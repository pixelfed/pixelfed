<?php

namespace App\Jobs\DeletePipeline;

use App\Profile;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Pool;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FanoutDeletePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $profile;

    public $timeout = 300;

    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($profile)
    {
        $this->profile = $profile;
    }

    public function handle()
    {
        $profile = $this->profile;

        // Verify profile exists
        if (! $profile) {
            Log::info('FanoutDeletePipeline: Profile no longer exists, skipping job');

            return;
        }

        // Verify profile has required fields for ActivityPub
        if (! $profile->permalink() || ! $profile->private_key) {
            Log::info("FanoutDeletePipeline: Profile {$profile->id} missing required fields for ActivityPub, skipping job");

            return;
        }

        try {
            $audience = Cache::remember('pf:ap:known_instances', now()->addHours(6), function () {
                return Profile::whereNotNull('sharedInbox')->groupBy('sharedInbox')->pluck('sharedInbox')->toArray();
            });

            $activity = [
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id' => $profile->permalink('#delete'),
                'type' => 'Delete',
                'actor' => $profile->permalink(),
                'object' => [
                    'type' => 'Person',
                    'id' => $profile->permalink(),
                ],
            ];

            $payload = json_encode($activity);
            $version = config('pixelfed.version');
            $appUrl = config('app.url');
            $userAgent = "(Pixelfed/{$version}; +{$appUrl})";
            $timeout = config('federation.activitypub.delivery.timeout');

            Http::pool(function (Pool $pool) use ($audience, $activity, $profile, $payload, $userAgent, $timeout) {
                foreach ($audience as $url) {
                    $curlHeaders = HttpSignature::sign($profile, $url, $activity, [
                        'Content-Type' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
                        'User-Agent' => $userAgent,
                    ]);

                    $headers = $this->parseCurlHeaders($curlHeaders);

                    $pool->withHeaders($headers)
                        ->timeout($timeout)
                        ->withBody($payload, 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"')
                        ->post($url);
                }
            });
        } catch (\Exception $e) {
            Log::warning("FanoutDeletePipeline: Failed to fanout delete for profile {$profile->id}: ".$e->getMessage());
            throw $e;
        }

        return 1;
    }

    /**
     * Convert curl-format header array ("Header: value") to associative array.
     *
     * @param  array<int, string>  $curlHeaders
     * @return array<string, string>
     */
    private function parseCurlHeaders(array $curlHeaders): array
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
