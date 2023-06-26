<?php

namespace App\Observers;

use App\Status;
use App\Services\ProfileStatusService;
use Cache;
use App\Models\ImportPost;
use App\Services\ImportService;

class StatusObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the Status "created" event.
     *
     * @param  \App\Status  $status
     * @return void
     */
    public function created(Status $status)
    {
        //
    }

    /**
     * Handle the Status "updated" event.
     *
     * @param  \App\Status  $status
     * @return void
     */
    public function updated(Status $status)
    {
        if(config('instance.timeline.home.cached')) {
            Cache::forget('pf:timelines:home:' . $status->profile_id);
        }

        if(in_array($status->scope, ['public', 'unlisted']) && in_array($status->type, ['photo', 'photo:album', 'video'])) {
            ProfileStatusService::add($status->profile_id, $status->id);
        }
    }

    /**
     * Handle the Status "deleted" event.
     *
     * @param  \App\Status  $status
     * @return void
     */
    public function deleted(Status $status)
    {
        if(config('instance.timeline.home.cached')) {
            Cache::forget('pf:timelines:home:' . $status->profile_id);
        }

        ProfileStatusService::delete($status->profile_id, $status->id);

        if($status->uri == null) {
            ImportPost::whereProfileId($status->profile_id)->whereStatusId($status->id)->delete();
            ImportService::clearImportedFiles($status->profile_id);
        }
    }

    /**
     * Handle the Status "restored" event.
     *
     * @param  \App\Status  $status
     * @return void
     */
    public function restored(Status $status)
    {
        //
    }

    /**
     * Handle the Status "force deleted" event.
     *
     * @param  \App\Status  $status
     * @return void
     */
    public function forceDeleted(Status $status)
    {
        //
    }
}
