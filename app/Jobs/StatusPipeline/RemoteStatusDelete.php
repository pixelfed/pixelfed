<?php

namespace App\Jobs\StatusPipeline;

use App\AccountInterstitial;
use App\Bookmark;
use App\CollectionItem;
use App\DirectMessage;
use App\Jobs\MediaPipeline\MediaDeletePipeline;
use App\Like;
use App\Media;
use App\MediaTag;
use App\Mention;
use App\Notification;
use App\Report;
use App\Services\Account\AccountStatService;
use App\Services\AccountService;
use App\Services\CollectionService;
use App\Services\NotificationService;
use App\Services\StatusService;
use App\Status;
use App\StatusArchived;
use App\StatusHashtag;
use App\StatusView;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

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

        if ($status->deleted_at) {
            return;
        }

        StatusService::del($status->id, true);
        AccountStatService::decrementPostCount($status->profile_id);

        return $this->unlinkRemoveMedia($status);
    }

    public function unlinkRemoveMedia($status)
    {

        if ($status->in_reply_to_id) {
            $parent = Status::find($status->in_reply_to_id);
            if ($parent) {
                $parent->reply_count--;
                $parent->save();
                StatusService::del($parent->id);
            }
        }

        AccountInterstitial::where('item_type', 'App\Status')
            ->where('item_id', $status->id)
            ->delete();
        Bookmark::whereStatusId($status->id)->delete();
        CollectionItem::whereObjectType('App\Status')
            ->whereObjectId($status->id)
            ->get()
            ->each(function ($col) {
                CollectionService::removeItem($col->collection_id, $col->object_id);
                $col->delete();
            });
        $dms = DirectMessage::whereStatusId($status->id)->get();
        foreach ($dms as $dm) {
            $not = Notification::whereItemType('App\DirectMessage')
                ->whereItemId($dm->id)
                ->first();
            if ($not) {
                NotificationService::del($not->profile_id, $not->id);
                $not->forceDeleteQuietly();
            }
            $dm->delete();
        }
        Like::whereStatusId($status->id)->forceDelete();
        Media::whereStatusId($status->id)
            ->get()
            ->each(function ($media) {
                MediaDeletePipeline::dispatch($media)->onQueue('mmo');
            });
        $mediaTags = MediaTag::where('status_id', $status->id)->get();
        foreach ($mediaTags as $mtag) {
            $not = Notification::whereItemType('App\MediaTag')
                ->whereItemId($mtag->id)
                ->first();
            if ($not) {
                NotificationService::del($not->profile_id, $not->id);
                $not->forceDeleteQuietly();
            }
            $mtag->delete();
        }
        Mention::whereStatusId($status->id)->forceDelete();
        Notification::whereItemType('App\Status')
            ->whereItemId($status->id)
            ->forceDelete();
        Report::whereObjectType('App\Status')
            ->whereObjectId($status->id)
            ->delete();
        StatusArchived::whereStatusId($status->id)->delete();
        StatusHashtag::whereStatusId($status->id)->delete();
        StatusView::whereStatusId($status->id)->delete();
        Status::whereInReplyToId($status->id)->update(['in_reply_to_id' => null]);

        $status->delete();

        StatusService::del($status->id, true);
        AccountService::del($status->profile_id);

        return 1;
    }
}
