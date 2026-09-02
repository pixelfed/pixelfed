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
use App\Services\Account\AccountStatService;
use App\Services\AccountService;
use App\Services\CollectionService;
use App\Services\NotificationService;
use App\Services\StatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RemoteStatusDelete implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    public $tries = 3;

    public $maxExceptions = 3;

    public $timeout = 180;

    public $failOnTimeout = true;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 3600;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'status:remote:delete:'.$this->status->id;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("status-remote-delete-{$this->status->id}"))->shared()->dontRelease()];
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Status $status)
    {
        $this->status = $status->withoutRelations();
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
            Log::info('RemoteStatusDelete: Status no longer exists, skipping job');

            return;
        }

        if ($status->deleted_at) {
            return;
        }

        StatusService::del($status->id, true);

        // AccountStatService::decrementPostCount($status->profile_id);
        return $this->unlinkRemoveMedia($status);
    }

    public function unlinkRemoveMedia($status)
    {

        if ($status->in_reply_to_id) {
            $parent = Status::find($status->in_reply_to_id);
            if ($parent) {
                if ($parent->reply_count) {
                    $parent->reply_count = $parent->reply_count - 1;
                    $parent->save();
                }
                StatusService::del($parent->id);
            }
        }

        AccountInterstitial::where('item_type', Status::class)
            ->where('item_id', $status->id)
            ->delete();
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
        Like::whereStatusId($status->id)->forceDelete();
        $media = Media::whereStatusId($status->id)->get();
        // Detach media from the status before dispatching deletion. status_id
        // has no FK/cascade, so it is not cleared when the status is deleted;
        // detaching here ensures the row is genuinely orphaned by the time the
        // MediaDeletePipeline guard checks it, so the delete is not skipped.
        Media::whereStatusId($status->id)->update(['status_id' => null]);
        $media->each(function ($m) {
            $m->status_id = null;
            MediaDeletePipeline::dispatch($m)->onQueue('mmo');
        });
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

        StatusService::del($status->id, true);
        AccountService::del($status->profile_id);

        $status->forceDelete();

        return 1;
    }
}
