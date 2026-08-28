<?php

namespace App\Jobs\SharePipeline;

use App\Jobs\HomeFeedPipeline\FeedRemovePipeline;
use App\Notification;
use App\Services\ReblogService;
use App\Services\StatusService;
use App\Status;
use App\Transformer\ActivityPub\Verb\UndoAnnounce;
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

class UndoSharePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;

    public $deleteWhenMissingModels = true;

    public function __construct(Status $status)
    {
        $this->status = $status;
    }

    public function handle()
    {
        $status = $this->status;
        $actor = $status->profile;
        $parent = Status::find($status->reblog_of_id);

        FeedRemovePipeline::dispatch($status->id, $status->profile_id)->onQueue('feed');

        if ($parent) {
            $target = $parent->profile_id;
            ReblogService::removePostReblog($parent->profile_id, $status->id);

            if ($parent->reblogs_count > 0) {
                $parent->reblogs_count = $parent->reblogs_count - 1;
                $parent->save();
                StatusService::del($parent->id);
            }

            $notification = Notification::whereProfileId($target)
                ->whereActorId($status->profile_id)
                ->whereAction('share')
                ->whereItemId($status->reblog_of_id)
                ->whereItemType(Status::class)
                ->first();

            if ($notification) {
                $notification->forceDelete();
            }
        }

        if ($status->uri != null) {
            return;
        }

        if (config('app.env') !== 'production' || (bool) config_cache('federation.activitypub.enabled') == false) {
            return $status->delete();
        } else {
            return $this->remoteAnnounceDeliver();
        }
    }

    public function remoteAnnounceDeliver()
    {
        if (config('app.env') !== 'production' || (bool) config_cache('federation.activitypub.enabled') == false) {
            $this->status->delete();

            return 1;
        }

        $status = $this->status;
        $profile = $status->profile;

        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);
        $resource = new Fractal\Resource\Item($status, new UndoAnnounce);
        $activity = $fractal->createData($resource)->toArray();

        $audience = $status->profile->getAudienceInbox();

        if (empty($audience) || $status->scope != 'public') {
            return 1;
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

        $status->delete();

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
