<?php

namespace App\Jobs\StoryPipeline;

use App\Services\FollowerService;
use App\Services\StoryIndexService;
use App\Services\StoryService;
use App\Story;
use App\Transformer\ActivityPub\Verb\DeleteStory;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Pool;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class StoryExpire implements ShouldQueue
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
            $this->handleRemoteExpiry();

            return;
        }

        if ($story->active == false) {
            return;
        }

        if ($story->expires_at->gt(now())) {
            return;
        }

        $story->active = false;
        $story->save();

        $this->rotateMediaPath();

        $index = app(StoryIndexService::class);
        $index->removeStory($story->id, $story->profile_id);

        $this->fanoutExpiry();

        StoryService::delLatest($story->profile_id);
    }

    protected function rotateMediaPath()
    {
        $story = $this->story;
        $date = date('Y').date('m');
        $old = $story->path;
        $base = "story_archives/{$story->profile_id}/{$date}/";
        $paths = explode('/', $old);
        $path = array_pop($paths);
        $newPath = $base.$path;

        if (Storage::exists($old) == true) {
            $dir = implode('/', $paths);
            Storage::move($old, $newPath);
            $story->bearcap_token = null;
            $story->path = $newPath;
            $story->save();

            $remainingFiles = Storage::files($dir);
            if (empty($remainingFiles)) {
                Storage::deleteDirectory($dir);
            }
        }
    }

    protected function fanoutExpiry()
    {
        $story = $this->story;
        $profile = $story->profile;

        if ($story->local == false || $story->remote_url) {
            return;
        }

        $audience = FollowerService::softwareAudience($story->profile_id, 'pixelfed');

        if (empty($audience)) {
            // Return on profiles with no remote followers
            return;
        }

        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);
        $resource = new Fractal\Resource\Item($story, new DeleteStory);
        $activity = $fractal->createData($resource)->toArray();

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

    protected function handleRemoteExpiry()
    {
        $story = $this->story;
        $story->active = false;
        $story->save();

        $index = app(StoryIndexService::class);
        $index->removeStory($story->id, $story->profile_id);

        $path = $story->path;

        if (Storage::exists($path) == true) {
            Storage::delete($path);
        }

        $story->views()->delete();
        $story->delete();
    }
}
