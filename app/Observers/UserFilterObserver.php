<?php

namespace App\Observers;

use App\UserFilter;
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
	 * @param  \App\UserFilter  $userFilter
	 * @return void
	 */
	public function created(UserFilter $userFilter)
	{
		$this->filterCreate($userFilter);
	}

	/**
	 * Handle the user filter "updated" event.
	 *
	 * @param  \App\UserFilter  $userFilter
	 * @return void
	 */
	public function updated(UserFilter $userFilter)
	{
		$this->filterCreate($userFilter);
	}

	/**
	 * Handle the user filter "deleted" event.
	 *
	 * @param  \App\UserFilter  $userFilter
	 * @return void
	 */
	public function deleted(UserFilter $userFilter)
	{
		$this->filterDelete($userFilter);
	}

	/**
	 * Handle the user filter "restored" event.
	 *
	 * @param  \App\UserFilter  $userFilter
	 * @return void
	 */
	public function restored(UserFilter $userFilter)
	{
		$this->filterCreate($userFilter);
	}

	/**
	 * Handle the user filter "force deleted" event.
	 *
	 * @param  \App\UserFilter  $userFilter
	 * @return void
	 */
	public function forceDeleted(UserFilter $userFilter)
	{
		$this->filterDelete($userFilter);
	}

	protected function filterCreate(UserFilter $userFilter)
	{
		if($userFilter->filterable_type !== 'App\Profile') {
			return;
		}

		switch ($userFilter->filter_type) {
			case 'mute':
				UserFilterService::mute($userFilter->user_id, $userFilter->filterable_id);
				break;
				
			case 'block':
				UserFilterService::block($userFilter->user_id, $userFilter->filterable_id);
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
				break;
				
			case 'block':
				UserFilterService::unblock($userFilter->user_id, $userFilter->filterable_id);
				break;
		}
	}
}
