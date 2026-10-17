<?php

namespace App\Observers;

use App\Jobs\HomeFeedPipeline\FeedFollowPipeline;
use App\Jobs\HomeFeedPipeline\FeedUnfollowPipeline;
use App\Models\Follower;
use App\Services\FollowerService;
use App\Services\StoryIndexService;

class FollowerObserver
{
    /**
     * Handle the Follower "created" event.
     *
     * @return void
     */
    public function created(Follower $follower)
    {
        FollowerService::add($follower->profile_id, $follower->following_id);
        app(StoryIndexService::class)->addFollowing($follower->profile_id, $follower->following_id);
        FeedFollowPipeline::dispatch($follower->profile_id, $follower->following_id)->onQueue('follow');
    }

    /**
     * Handle the Follower "deleted" event.
     *
     * @return void
     */
    public function deleted(Follower $follower)
    {
        FollowerService::remove($follower->profile_id, (string) $follower->following_id);
        app(StoryIndexService::class)->removeFollowing($follower->profile_id, $follower->following_id);
        FeedUnfollowPipeline::dispatch($follower->profile_id, $follower->following_id)->onQueue('feed');
    }

    /**
     * Handle the Follower "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(Follower $follower)
    {
        FollowerService::remove($follower->profile_id, (string) $follower->following_id);
        app(StoryIndexService::class)->removeFollowing($follower->profile_id, $follower->following_id);
    }
}
