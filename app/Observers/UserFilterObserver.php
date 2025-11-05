<?php

namespace App\Observers;

use App\UserFilter;
use App\Services\UserFilterService;
use App\Jobs\HomeFeedPipeline\FeedFollowPipeline;
use App\Jobs\HomeFeedPipeline\FeedUnfollowPipeline;

class UserFilterObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

	protected function filterCreate(UserFilter $userFilter)
	{
		if($userFilter->filterable_type !== 'App\Profile') {
			return;
		}

		switch ($userFilter->filter_type) {
			case 'mute':
				UserFilterService::mute($userFilter->user_id, $userFilter->filterable_id);
				FeedUnfollowPipeline::dispatch($userFilter->user_id, $userFilter->filterable_id)->onQueue('feed');
				break;
				
			case 'block':
				UserFilterService::block($userFilter->user_id, $userFilter->filterable_id);
				FeedUnfollowPipeline::dispatch($userFilter->user_id, $userFilter->filterable_id)->onQueue('feed');
				break;
		}
	}


	protected function filterDelete(UserFilter $userFilter)
	{
		if($userFilter->filterable_type !== 'App\Profile') {
			return;
		}

		switch ($userFilter->filter_type) {
			case 'mute':
				UserFilterService::unmute($userFilter->user_id, $userFilter->filterable_id);
				FeedFollowPipeline::dispatch($userFilter->user_id, $userFilter->filterable_id)->onQueue('feed');
				break;
				
			case 'block':
				UserFilterService::unblock($userFilter->user_id, $userFilter->filterable_id);
				FeedFollowPipeline::dispatch($userFilter->user_id, $userFilter->filterable_id)->onQueue('feed');
				break;
		}
	}
}
