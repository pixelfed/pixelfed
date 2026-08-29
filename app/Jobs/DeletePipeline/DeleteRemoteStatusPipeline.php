<?php

namespace App\Jobs\DeletePipeline;

use App\Jobs\MediaPipeline\MediaDeletePipeline;
use App\Models\Bookmark;
use App\Models\DirectMessage;
use App\Models\Like;
use App\Models\Media;
use App\Models\MediaTag;
use App\Models\Mention;
use App\Models\Notification;
use App\Models\Report;
use App\Models\Status;
use App\Models\StatusHashtag;
use App\Models\StatusView;
use App\Services\Account\AccountStatService;
use App\Services\NetworkTimelineService;
use App\Services\StatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

        // Verify status exists
        if (! $status) {
            Log::info('DeleteRemoteStatusPipeline: Status no longer exists, skipping job');

            return;
        }

        // Verify status has a profile
        if (! $status->profile_id) {
            Log::info("DeleteRemoteStatusPipeline: Status {$status->id} has no profile_id, skipping job");

            return;
        }

        try {
            AccountStatService::decrementPostCount($status->profile_id);
            NetworkTimelineService::del($status->id);
            StatusService::del($status->id, true);
            Bookmark::whereStatusId($status->id)->delete();
            Notification::whereItemType(Status::class)
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
            Report::whereObjectType(Status::class)->whereObjectId($status->id)->delete();
            StatusHashtag::whereStatusId($status->id)->delete();
            StatusView::whereStatusId($status->id)->delete();
            Status::whereReblogOfId($status->id)->forceDelete();
            $status->forceDelete();
        } catch (\Exception $e) {
            Log::warning("DeleteRemoteStatusPipeline: Failed to delete status {$status->id}: ".$e->getMessage());
            throw $e;
        }

        return 1;
    }
}
