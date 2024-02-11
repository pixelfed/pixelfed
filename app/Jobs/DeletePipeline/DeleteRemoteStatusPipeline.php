<?php

namespace App\Jobs\DeletePipeline;

use App\Bookmark;
use App\DirectMessage;
use App\Jobs\MediaPipeline\MediaDeletePipeline;
use App\Like;
use App\Media;
use App\MediaTag;
use App\Mention;
use App\Notification;
use App\Report;
use App\Services\Account\AccountStatService;
use App\Services\NetworkTimelineService;
use App\Services\StatusService;
use App\Status;
use App\StatusHashtag;
use App\StatusView;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteRemoteStatusPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;

    public $timeout = 30;

    public $tries = 2;

    public $maxExceptions = 1;

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

        AccountStatService::decrementPostCount($status->profile_id);
        NetworkTimelineService::del($status->id);
        StatusService::del($status->id, true);
        Bookmark::whereStatusId($status->id)->delete();
        Notification::whereItemType('App\Status')
            ->whereItemId($status->id)
            ->forceDelete();
        DirectMessage::whereStatusId($status->id)->delete();
        Like::whereStatusId($status->id)->forceDelete();
        MediaTag::whereStatusId($status->id)->delete();
        Media::whereStatusId($status->id)
            ->get()
            ->each(function ($media) {
                MediaDeletePipeline::dispatch($media)->onQueue('mmo');
            });
        Mention::whereStatusId($status->id)->forceDelete();
        Report::whereObjectType('App\Status')->whereObjectId($status->id)->delete();
        StatusHashtag::whereStatusId($status->id)->delete();
        StatusView::whereStatusId($status->id)->delete();
        Status::whereReblogOfId($status->id)->forceDelete();
        $status->forceDelete();

        return 1;
    }
}
