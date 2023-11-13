<?php

namespace App\Observers;

use App\Follower;
use App\Services\FollowerService;
use Cache;
use App\Jobs\HomeFeedPipeline\FeedFollowPipeline;
use App\Jobs\HomeFeedPipeline\FeedUnfollowPipeline;

class FollowerObserver
{
    /**
     * Handle the Follower "created" event.
     *
     * @param  \App\Follower  $follower
     * @return void
     */
    public function created(Follower $follower)
    {
        if(config('instance.timeline.home.cached')) {
            Cache::forget('pf:timelines:home:' . $follower->profile_id);
        }

        FollowerService::add($follower->profile_id, $follower->following_id);
        FeedFollowPipeline::dispatch($follower->profile_id, $follower->following_id)->onQueue('follow');
    }

    /**
     * Handle the Follower "deleted" event.
     *
     * @param  \App\Follower  $follower
     * @return void
     */
    public function deleted(Follower $follower)
    {
        FollowerService::remove($follower->profile_id, (string) $follower->following_id);
    }

    /**
     * Handle the Follower "force deleted" event.
     *
     * @param  \App\Follower  $follower
     * @return void
     */
    public function forceDeleted(Follower $follower)
    {
        FollowerService::remove($follower->profile_id, (string) $follower->following_id);
    }
}
