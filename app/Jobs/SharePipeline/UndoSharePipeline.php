<?php

namespace App\Jobs\SharePipeline;

use App\Jobs\HomeFeedPipeline\FeedRemovePipeline;
use App\Notification;
use App\Services\FractalService;
use App\Services\ReblogService;
use App\Services\StatusService;
use App\Status;
use App\Transformer\ActivityPub\Verb\UndoAnnounce;
use App\Util\ActivityPub\HttpSignature;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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

        $activity = FractalService::item($status, new UndoAnnounce);

        $audience = $status->profile->getAudienceInbox();

        if (empty($audience) || $status->scope != 'public') {
            return 1;
        }

        $payload = json_encode($activity);

        $client = new Client([
            'timeout' => config('federation.activitypub.delivery.timeout'),
        ]);

        $version = config('pixelfed.version');
        $appUrl = config('app.url');
        $userAgent = "(Pixelfed/{$version}; +{$appUrl})";

        $requests = function ($audience) use ($client, $activity, $profile, $payload, $userAgent) {
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

        $pool = new Pool($client, $requests($audience), [
            'concurrency' => config('federation.activitypub.delivery.concurrency'),
            'fulfilled' => function ($response, $index) {},
            'rejected' => function ($reason, $index) {},
        ]);

        $promise = $pool->promise();

        $promise->wait();

        $status->delete();

        return 1;
    }
}
