<?php

namespace App\Jobs\StatusPipeline;

use App\Jobs\MediaPipeline\MediaDeletePipeline;
use App\Models\AccountInterstitial;
use App\Models\Bookmark;
use App\Models\CollectionItem;
use App\Models\DirectMessage;
use App\Models\Like;
use App\Models\Media;
use App\Models\MediaTag;
use App\Models\Mention;
use App\Models\Notification;
use App\Models\Report;
use App\Models\Status;
use App\Models\StatusArchived;
use App\Models\StatusHashtag;
use App\Models\StatusView;
use App\Services\CollectionService;
use App\Services\NotificationService;
use App\Services\StatusService;
use App\Transformer\ActivityPub\Verb\DeleteNote;
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
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class StatusDelete implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    public $timeout = 900;

    public $tries = 2;

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

        // Verify status exists
        if (! $status) {
            Log::info('StatusDelete: Status no longer exists, skipping job');

            return;
        }

        $profile = $status->profile()->withTrashed()->first();

        // Verify profile exists
        if (! $profile) {
            Log::info("StatusDelete: Profile no longer exists for status {$status->id}, skipping job");

            return;
        }

        StatusService::del($status->id, true);
        if ($profile) {
            if (in_array($status->type, ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])) {
                $profile->status_count = $profile->status_count - 1;
                $profile->save();
            }
        }

        Cache::forget('pf:atom:user-feed:by-id:'.$status->profile_id);

        if ((bool) config_cache('federation.activitypub.enabled') == true) {
            return $this->fanoutDelete($status);
        } else {
            return $this->unlinkRemoveMedia($status);
        }
    }

    public function unlinkRemoveMedia($status)
    {
        Media::whereStatusId($status->id)
            ->get()
            ->each(function ($media) {
                MediaDeletePipeline::dispatch($media);
            });

        if ($status->in_reply_to_id) {
            $parent = Status::findOrFail($status->in_reply_to_id);
            $parent->reply_count--;
            $parent->save();
            StatusService::del($parent->id);
        }

        Bookmark::whereStatusId($status->id)->delete();

        CollectionItem::whereObjectType(Status::class)
            ->whereObjectId($status->id)
            ->get()
            ->each(function ($col) {
                CollectionService::removeItem($col->collection_id, $col->object_id);
                $col->delete();
            });

        $dms = DirectMessage::whereStatusId($status->id)->get();
        foreach ($dms as $dm) {
            $not = Notification::whereItemType(DirectMessage::class)
                ->whereItemId($dm->id)
                ->first();
            if ($not) {
                NotificationService::del($not->profile_id, $not->id);
                $not->forceDeleteQuietly();
            }
            $dm->delete();
        }
        Like::whereStatusId($status->id)->delete();

        $mediaTags = MediaTag::where('status_id', $status->id)->get();
        foreach ($mediaTags as $mtag) {
            $not = Notification::whereItemType(MediaTag::class)
                ->whereItemId($mtag->id)
                ->first();
            if ($not) {
                NotificationService::del($not->profile_id, $not->id);
                $not->forceDeleteQuietly();
            }
            $mtag->delete();
        }
        Mention::whereStatusId($status->id)->forceDelete();

        Notification::whereItemType(Status::class)
            ->whereItemId($status->id)
            ->forceDelete();

        Report::whereObjectType(Status::class)
            ->whereObjectId($status->id)
            ->delete();

        StatusArchived::whereStatusId($status->id)->delete();
        StatusHashtag::whereStatusId($status->id)->delete();
        StatusView::whereStatusId($status->id)->delete();
        Status::whereInReplyToId($status->id)->update(['in_reply_to_id' => null]);

        AccountInterstitial::where('item_type', Status::class)
            ->where('item_id', $status->id)
            ->delete();

        $status->delete();

        return 1;
    }

    public function fanoutDelete($status)
    {
        $profile = $status->profile()->withTrashed()->first();

        if (! $profile) {
            return;
        }

        $audience = $status->profile->getAudienceInbox();

        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);
        $resource = new Fractal\Resource\Item($status, new DeleteNote);
        $activity = $fractal->createData($resource)->toArray();

        $this->unlinkRemoveMedia($status);

        $payload = json_encode($activity);

        $version = config('pixelfed.version');
        $appUrl = config('app.url');
        $userAgent = "(Pixelfed/{$version}; +{$appUrl})";
        $timeout = config('federation.activitypub.delivery.timeout');

        $responses = Http::pool(function (Pool $pool) use ($audience, $activity, $profile, $payload, $userAgent, $timeout) {
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
