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
use App\Models\QuoteAuthorization;
use App\Models\Report;
use App\Models\Status;
use App\Models\StatusArchived;
use App\Models\StatusEdit;
use App\Models\StatusHashtag;
use App\Models\StatusView;
use App\Services\ActivityPubDeliveryService;
use App\Services\CollectionService;
use App\Services\DirectMessageService;
use App\Services\FractalService;
use App\Services\NotificationService;
use App\Services\Status\ReplyCleanupService;
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

        if ((bool) config_cache('federation.activitypub.enabled') === true) {
            $this->fanoutDelete($status);

            return;
        }

        $this->unlinkRemoveMedia($status);
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
            $parent = Status::find($status->in_reply_to_id);
            if ($parent) {
                $parent->reply_count = max(0, $parent->reply_count - 1);
                $parent->save();
                StatusService::del($parent->id);
            }
        }

        Bookmark::whereStatusId($status->id)->delete();

        QuoteAuthorization::whereStatusId($status->id)->delete();

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
        app(DirectMessageService::class)->deleteByStatusId($status->id);
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

        // Per-row (not bulk) so NotificationObserver::forceDeleted fires and
        // NotificationService::del invalidates the 24h cached ITEM_KEY snapshot;
        // a bulk forceDelete() would leave the web feed serving the deleted
        // status as a ghost. Match the legacy 'App\Status' morph alias too.
        Notification::whereIn('item_type', ['App\Status', Status::class])
            ->where('item_id', $status->id)
            ->cursor()
            ->each(function ($not) {
                NotificationService::del($not->profile_id, $not->id);
                $not->forceDeleteQuietly();
            });

        Report::whereObjectType(Status::class)
            ->whereObjectId($status->id)
            ->delete();

        StatusArchived::whereStatusId($status->id)->delete();
        // Purge edit history so single-status deletion doesn't leave prior
        // caption/CW versions behind (status_edits has no FK/cascade).
        StatusEdit::whereStatusId($status->id)->delete();
        // Model-based delete so StatusHashtagObserver::deleted() runs and
        // decrements hashtags.cached_count (a query-builder delete bypasses it).
        StatusHashtag::whereStatusId($status->id)->get()->each->delete();
        StatusView::whereStatusId($status->id)->delete();
        ReplyCleanupService::releaseRepliesOf($status);

        AccountInterstitial::where('item_type', Status::class)
            ->where('item_id', $status->id)
            ->delete();

        $statusId = $status->id;
        $status->delete();

        StatusService::del($statusId, true);

        return 1;
    }

    public function fanoutDelete($status)
    {
        $profile = $status->profile()->withTrashed()->first();

        if (! $profile) {
            return null;
        }

        $status->setRelation('profile', $profile);

        $audience = array_values($profile->getAudienceInbox());
        $activity = FractalService::item($status, new DeleteNote);

        Log::info('StatusDelete: fanout', [
            'status_id' => $status->id,
            'actor' => $activity['actor'] ?? null,
            'object' => $activity['object']['id'] ?? $activity['object'] ?? null,
            'inboxes' => count($audience),
        ]);

        // Isolate federation delivery from local cleanup. pool() can throw
        // synchronously (e.g. validateSender() rejects an inactive sender during
        // account deletion, where profiles.status = 'delete'). If that exception
        // escaped, unlinkRemoveMedia() — the whole point of this job — would be
        // skipped and the status + its data would leak. Delivery is best-effort;
        // local deletion is not.
        try {
            ActivityPubDeliveryService::pool($profile, $audience, $activity, function ($res, $i) use ($audience, $status) {
                Log::warning('StatusDelete: delivery failed', [
                    'status_id' => $status->id,
                    'inbox' => $audience[$i] ?? null,
                    'result' => $res instanceof \Throwable
                        ? $res::class.': '.$res->getMessage()
                        : $res->status().' '.substr($res->body(), 0, 300),
                ]);
            });
        } catch (\Throwable $e) {
            Log::warning('StatusDelete: delivery aborted, proceeding to local cleanup', [
                'status_id' => $status->id,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);
        }

        $this->unlinkRemoveMedia($status);

        return 1;
    }
}
