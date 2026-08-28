<?php

namespace App\Jobs\SharePipeline;

use App\Jobs\HomeFeedPipeline\FeedInsertPipeline;
use App\Models\Notification;
use App\Models\Status;
use App\Services\ReblogService;
use App\Services\StatusService;
use App\Transformer\ActivityPub\Verb\Announce;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Pool;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class SharePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;

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
    public function __construct(Status $status)
    {
        $this->status = $status;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $status = $this->status;
        $parent = Status::find($this->status->reblog_of_id);
        if (! $parent) {
            return;
        }
        $actor = $status->profile;
        $target = $parent->profile;

        if ($status->uri !== null) {
            // Ignore notifications to remote statuses
            return;
        }

        if ($target->id === $status->profile_id) {
            $this->remoteAnnounceDeliver();

            return true;
        }

        ReblogService::addPostReblog($parent->profile_id, $status->id);

        $parent->reblogs_count = $parent->reblogs_count + 1;
        $parent->save();
        StatusService::del($parent->id);

        Notification::firstOrCreate(
            [
                'profile_id' => $target->id,
                'actor_id' => $actor->id,
                'action' => 'share',
                'item_type' => Status::class,
                'item_id' => $status->reblog_of_id ?? $status->id,
            ]
        );

        FeedInsertPipeline::dispatch($status->id, $status->profile_id)->onQueue('feed');

        return $this->remoteAnnounceDeliver();
    }

    public function remoteAnnounceDeliver()
    {
        if (config('app.env') !== 'production' || (bool) config_cache('federation.activitypub.enabled') == false) {
            return true;
        }
        $status = $this->status;
        $profile = $status->profile;

        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);
        $resource = new Fractal\Resource\Item($status, new Announce);
        $activity = $fractal->createData($resource)->toArray();

        $audience = $status->profile->getAudienceInbox();

        if (empty($audience) || $status->scope != 'public') {
            // Return on profiles with no remote followers
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
