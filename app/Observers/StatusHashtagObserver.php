<?php

namespace App\Observers;

use App\StatusHashtag;
use App\Services\StatusHashtagService;

class StatusHashtagObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the notification "created" event.
     *
     * @param  \App\Notification  $notification
     * @return void
     */
    public function created(StatusHashtag $hashtag)
    {
        StatusHashtagService::set($hashtag->hashtag_id, $hashtag->status_id);
    }

    /**
     * Handle the notification "updated" event.
     *
     * @param  \App\Notification  $notification
     * @return void
     */
    public function updated(StatusHashtag $hashtag)
    {
        StatusHashtagService::set($hashtag->hashtag_id, $hashtag->status_id);
    }

    /**
     * Handle the notification "deleted" event.
     *
     * @param  \App\Notification  $notification
     * @return void
     */
    public function deleted(StatusHashtag $hashtag)
    {
        StatusHashtagService::del($hashtag->hashtag_id, $hashtag->status_id);
    }

    /**
     * Handle the notification "restored" event.
     *
     * @param  \App\Notification  $notification
     * @return void
     */
    public function restored(StatusHashtag $hashtag)
    {
        StatusHashtagService::set($hashtag->hashtag_id, $hashtag->status_id);
    }

    /**
     * Handle the notification "force deleted" event.
     *
     * @param  \App\Notification  $notification
     * @return void
     */
    public function forceDeleted(StatusHashtag $hashtag)
    {
        StatusHashtagService::del($hashtag->hashtag_id, $hashtag->status_id);
    }
}
