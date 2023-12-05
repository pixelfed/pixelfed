<?php

namespace App\Observers;

use DB;
use App\StatusHashtag;
use App\Services\StatusHashtagService;
use App\Jobs\HomeFeedPipeline\HashtagInsertFanoutPipeline;
use App\Jobs\HomeFeedPipeline\HashtagRemoveFanoutPipeline;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class StatusHashtagObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the notification "created" event.
     *
     * @param  \App\StatusHashtag  $hashtag
     * @return void
     */
    public function created(StatusHashtag $hashtag)
    {
        StatusHashtagService::set($hashtag->hashtag_id, $hashtag->status_id);
        DB::table('hashtags')->where('id', $hashtag->hashtag_id)->increment('cached_count');
        if($hashtag->status_visibility && $hashtag->status_visibility === 'public') {
            HashtagInsertFanoutPipeline::dispatch($hashtag)->onQueue('feed');
        }
    }

    /**
     * Handle the notification "updated" event.
     *
     * @param  \App\StatusHashtag  $hashtag
     * @return void
     */
    public function updated(StatusHashtag $hashtag)
    {
        StatusHashtagService::set($hashtag->hashtag_id, $hashtag->status_id);
    }

    /**
     * Handle the notification "deleted" event.
     *
     * @param  \App\StatusHashtag  $hashtag
     * @return void
     */
    public function deleted(StatusHashtag $hashtag)
    {
        StatusHashtagService::del($hashtag->hashtag_id, $hashtag->status_id);
        DB::table('hashtags')->where('id', $hashtag->hashtag_id)->decrement('cached_count');
        if($hashtag->status_visibility && $hashtag->status_visibility === 'public') {
            HashtagRemoveFanoutPipeline::dispatch($hashtag->status_id, $hashtag->hashtag_id)->onQueue('feed');
        }
    }

    /**
     * Handle the notification "restored" event.
     *
     * @param  \App\StatusHashtag  $hashtag
     * @return void
     */
    public function restored(StatusHashtag $hashtag)
    {
        StatusHashtagService::set($hashtag->hashtag_id, $hashtag->status_id);
    }

    /**
     * Handle the notification "force deleted" event.
     *
     * @param  \App\StatusHashtag  $hashtag
     * @return void
     */
    public function forceDeleted(StatusHashtag $hashtag)
    {
        StatusHashtagService::del($hashtag->hashtag_id, $hashtag->status_id);
    }
}
