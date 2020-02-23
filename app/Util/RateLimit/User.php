<?php

namespace App\Util\RateLimit;

trait User {
	
	public function isTrustedAccount()
	{
		return $this->created_at->lt(now()->subDays(60));
	}

	public function getMaxPostsPerHourAttribute(): int
	{
		return 50;
	}

	public function getMaxPostsPerDayAttribute(): int
	{
		return 100;
	}

	public function getMaxCommentsPerHourAttribute(): int
	{
		return 50;
	}

	public function getMaxCommentsPerDayAttribute(): int
	{
		return 500;
	}

	public function getMaxLikesPerHourAttribute(): int
	{
		return 120;
	}

	public function getMaxLikesPerDayAttribute(): int
	{
		return 1000;
	}

	public function getMaxSharesPerHourAttribute(): int
	{
		return 60;
	}

	public function getMaxSharesPerDayAttribute(): int
	{
		return 500;
	}

	public function getMaxUserBansPerDayAttribute(): int
	{
		return 100;
	}

	public function getMaxInstanceBansPerDayAttribute(): int
	{
		return 100;
	}

	public function getMaxHashtagFollowsPerHourAttribute(): int
	{
		return 20;
	}

	public function getMaxHashtagFollowsPerDayAttribute(): int
	{
		return 100;
	}

	public function getMaxCollectionsPerHourAttribute(): int
	{
		return 10;
	}

	public function getMaxCollectionsPerDayAttribute(): int
	{
		return 20;
	}

	public function getMaxCollectionsPerMonthAttribute(): int
	{
		return 100;
	}

	public function getMaxComposeMediaUpdatesPerHourAttribute(): int
	{
		return 100;
	}

	public function getMaxComposeMediaUpdatesPerDayAttribute(): int
	{
		return 1000;
	}

	public function getMaxComposeMediaUpdatesPerMonthAttribute(): int
	{
		return 5000;
	}

	public function getMaxStoriesPerHourAttribute(): int
	{
		return 20;
	}

	public function getMaxStoriesPerDayAttribute(): int
	{
		return 30;
	}

	public function getMaxStoryDeletePerDayAttribute(): int
	{
		return 35;
	}

	public function getMaxPostEditsPerHourAttribute(): int
	{
		return 10;
	}

	public function getMaxPostEditsPerDayAttribute(): int
	{
		return 20;
	}
}