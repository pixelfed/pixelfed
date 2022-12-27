<?php

namespace App\Observers;

use DB;
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
        DB::table('hashtags')->where('id', $hashtag->hashtag_id)->increment('cached_count');
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
        DB::table('hashtags')->where('id', $hashtag->hashtag_id)->decrement('cached_count');
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
