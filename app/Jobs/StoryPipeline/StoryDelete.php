<?php

namespace App\Jobs\StoryPipeline;

use App\Models\Story;
use App\Services\FollowerService;
use App\Services\StoryService;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Pool;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class StoryDelete implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $story;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Story $story)
    {
        $this->story = $story;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $story = $this->story;

        if ($story->local == false) {
            return;
        }

        StoryService::removeRotateQueue($story->id);
        StoryService::delLatest($story->profile_id);
        StoryService::delById($story->id);

        if (Storage::exists($story->path) == true) {
            Storage::delete($story->path);
        }

        $story->views()->delete();

        $profile = $story->profile;

        $activity = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $story->url().'#delete',
            'type' => 'Delete',
            'actor' => $profile->permalink(),
            'object' => [
                'id' => $story->url(),
                'type' => 'Story',
            ],
        ];

        $this->fanoutExpiry($profile, $activity);

        // delete notifications
        // delete polls
        // delete reports

        $story->delete();

    }

    protected function fanoutExpiry($profile, $activity)
    {
        $audience = FollowerService::softwareAudience($profile->id, 'pixelfed');

        if (empty($audience)) {
            return;
        }

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
