<?php

namespace App\Util\RateLimit;

trait User {
	
	public function isTrustedAccount()
	{
		return $this->created_at->lt(now()->subDays(60));
	}

	public function getMaxPostsPerHourAttribute()
	{
		return 50;
	}

	public function getMaxPostsPerDayAttribute()
	{
		return 100;
	}

	public function getMaxCommentsPerHourAttribute()
	{
		return 50;
	}

	public function getMaxCommentsPerDayAttribute()
	{
		return 500;
	}

	public function getMaxLikesPerHourAttribute()
	{
		return 120;
	}

	public function getMaxLikesPerDayAttribute()
	{
		return 1000;
	}

	public function getMaxSharesPerHourAttribute()
	{
		return 60;
	}

	public function getMaxSharesPerDayAttribute()
	{
		return 500;
	}

	public function getMaxUserBansPerDayAttribute()
	{
		return 100;
	}

	public function getMaxInstanceBansPerDayAttribute()
	{
		return 100;
	}

	public function getMaxHashtagFollowsPerHourAttribute()
	{
		return 20;
	}

	public function getMaxHashtagFollowsPerDayAttribute()
	{
		return 100;
	}

	public function getMaxCollectionsPerHourAttribute()
	{
		return 10;
	}

	public function getMaxCollectionsPerDayAttribute()
	{
		return 20;
	}

	public function getMaxCollectionsPerMonthAttribute()
	{
		return 100;
	}

	public function getMaxComposeMediaUpdatesPerHourAttribute()
	{
		return 100;
	}

	public function getMaxComposeMediaUpdatesPerDayAttribute()
	{
		return 1000;
	}

	public function getMaxComposeMediaUpdatesPerMonthAttribute()
	{
		return 5000;
	}

	public function getMaxStoriesPerHourAttribute()
	{
		return 20;
	}

	public function getMaxStoriesPerDayAttribute()
	{
		return 30;
	}

	public function getMaxStoryDeletePerDayAttribute()
	{
		return 35;
	}

	public function getMaxPostEditsPerHourAttribute()
	{
		return 10;
	}

	public function getMaxPostEditsPerDayAttribute()
	{
		return 20;
	}
}