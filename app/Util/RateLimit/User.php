<?php

namespace App\Util\RateLimit;

trait User {
	
	public function isTrustedAccount()
	{
		return $this->created_at->lt(now()->subDays(20));
	}

	public function getMaxPostsPerHourAttribute()
	{
		return 20;
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

	public function getMaxInstanceBansPerDayAttribute()
	{
		return 100;
	}
}