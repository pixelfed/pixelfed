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
use App\Services\ActivityPubDeliveryService;
use App\Services\CollectionService;
use App\Services\FractalService;
use App\Services\NotificationService;
use App\Services\StatusService;
use App\Transformer\ActivityPub\Verb\DeleteNote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
        $media = Media::whereStatusId($status->id)->get();
        // Detach media from the status before dispatching deletion. status_id
        // has no FK/cascade, so it is not cleared when the status is deleted;
        // detaching here ensures the row is genuinely orphaned by the time the
        // MediaDeletePipeline guard checks it, so the delete is not skipped.
        Media::whereStatusId($status->id)->update(['status_id' => null]);
        $media->each(function ($m) {
            $m->status_id = null;
            MediaDeletePipeline::dispatch($m);
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

        $dmIds = DirectMessage::whereStatusId($status->id)->pluck('id');
        if ($dmIds->isNotEmpty()) {
            Notification::whereItemType(DirectMessage::class)
                ->whereIn('item_id', $dmIds)
                ->cursor()
                ->each(function ($not) {
                    NotificationService::del($not->profile_id, $not->id);
                    $not->forceDeleteQuietly();
                });
            DirectMessage::whereIn('id', $dmIds)->delete();
        }
        Like::whereStatusId($status->id)->delete();

        $mediaTagIds = MediaTag::where('status_id', $status->id)->pluck('id');
        if ($mediaTagIds->isNotEmpty()) {
            Notification::whereItemType(MediaTag::class)
                ->whereIn('item_id', $mediaTagIds)
                ->cursor()
                ->each(function ($not) {
                    NotificationService::del($not->profile_id, $not->id);
                    $not->forceDeleteQuietly();
                });
            MediaTag::whereIn('id', $mediaTagIds)->delete();
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

        $activity = FractalService::item($status, new DeleteNote);

        $this->unlinkRemoveMedia($status);

        ActivityPubDeliveryService::pool($profile, $audience, $activity);

        return 1;
    }
}
