<?php

namespace App\Observers;

use App\HashtagFollow;
use App\Services\HashtagFollowService;
use App\Jobs\HomeFeedPipeline\HashtagUnfollowPipeline;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class HashtagFollowObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the HashtagFollow "created" event.
     */
    public function created(HashtagFollow $hashtagFollow): void
    {
        HashtagFollowService::add($hashtagFollow->hashtag_id, $hashtagFollow->profile_id);
    }

    /**
     * Handle the HashtagFollow "updated" event.
     */
    public function updated(HashtagFollow $hashtagFollow): void
    {
    	//
    }

    /**
     * Handle the HashtagFollow "deleting" event.
     */
    public function deleting(HashtagFollow $hashtagFollow): void
    {
        HashtagFollowService::unfollow($hashtagFollow->hashtag_id, $hashtagFollow->profile_id);
    }

    /**
     * Handle the HashtagFollow "restored" event.
     */
    public function restored(HashtagFollow $hashtagFollow): void
    {
        //
    }

    /**
     * Handle the HashtagFollow "force deleted" event.
     */
    public function forceDeleted(HashtagFollow $hashtagFollow): void
    {
        HashtagFollowService::unfollow($hashtagFollow->hashtag_id, $hashtagFollow->profile_id);
    }
}
