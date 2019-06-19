<?php

namespace App\Util\RateLimit;

trait User {
	
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
}