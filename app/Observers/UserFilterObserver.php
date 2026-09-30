<?php

namespace App\Observers;

use App\Jobs\HomeFeedPipeline\FeedFollowPipeline;
use App\Jobs\HomeFeedPipeline\FeedUnfollowPipeline;
use App\Models\Profile;
use App\Models\UserFilter;
use App\Services\BlockSyncService;
use App\Services\FeaturedCollectionService;
use App\Services\QuoteService;
use App\Services\UserFilterService;

class UserFilterObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the user filter "created" event.
     *
     * @return void
     */
    public function created(UserFilter $userFilter)
    {
        $this->filterCreate($userFilter);
    }

    /**
     * Handle the user filter "updated" event.
     *
     * @return void
     */
    public function updated(UserFilter $userFilter)
    {
        // Not a new block: re-running the federation side would toggle the
        // pair out of the FEP-070c digest and send a duplicate Block.
        $this->filterCreate($userFilter, false);
    }

    /**
     * Handle the user filter "deleted" event.
     *
     * @return void
     */
    public function deleted(UserFilter $userFilter)
    {
        $this->filterDelete($userFilter);
    }

    /**
     * Handle the user filter "restored" event.
     *
     * @return void
     */
    public function restored(UserFilter $userFilter)
    {
        $this->filterCreate($userFilter);
    }

    /**
     * Handle the user filter "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(UserFilter $userFilter)
    {
        $this->filterDelete($userFilter);
    }

    protected function filterCreate(UserFilter $userFilter, bool $federate = true)
    {
        if ($userFilter->filterable_type !== Profile::class) {
            return;
        }

        switch ($userFilter->filter_type) {
            case 'mute':
                UserFilterService::mute($userFilter->user_id, $userFilter->filterable_id);
                FeedUnfollowPipeline::dispatch($userFilter->user_id, $userFilter->filterable_id)->onQueue('feed');
                break;

            case 'block':
                UserFilterService::block($userFilter->user_id, $userFilter->filterable_id);

                if ($this->isRemoteBlocker($userFilter)) {
                    FeedUnfollowPipeline::dispatch($userFilter->filterable_id, $userFilter->user_id)->onQueue('feed');
                    break;
                }

                FeedUnfollowPipeline::dispatch($userFilter->user_id, $userFilter->filterable_id)->onQueue('feed');
                // user_id is the blocking profile id, filterable_id the blocked profile
                FeaturedCollectionService::revokeForActor($userFilter->user_id, $userFilter->filterable_id);
                QuoteService::revokeForActor($userFilter->user_id, $userFilter->filterable_id);
                if ($federate) {
                    BlockSyncService::localBlockChanged($userFilter, true);
                }
                break;
        }
    }

    protected function filterDelete(UserFilter $userFilter)
    {
        if ($userFilter->filterable_type !== Profile::class) {
            return;
        }

        switch ($userFilter->filter_type) {
            case 'mute':
                UserFilterService::unmute($userFilter->user_id, $userFilter->filterable_id);
                FeedFollowPipeline::dispatch($userFilter->user_id, $userFilter->filterable_id)->onQueue('feed');
                break;

            case 'block':
                UserFilterService::unblock($userFilter->user_id, $userFilter->filterable_id);

                if ($this->isRemoteBlocker($userFilter)) {
                    break;
                }

                FeedFollowPipeline::dispatch($userFilter->user_id, $userFilter->filterable_id)->onQueue('feed');
                BlockSyncService::localBlockChanged($userFilter, false);
                break;
        }
    }

    protected function isRemoteBlocker(UserFilter $userFilter): bool
    {
        return Profile::withTrashed()
            ->whereKey($userFilter->user_id)
            ->whereNotNull('domain')
            ->exists();
    }
}
