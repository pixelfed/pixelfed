<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Status;

class DiscoverService
{
	public static function getDailyIdPool()
	{
		$min_id = SnowflakeService::byDate(now()->subMonths(3));
		return Status::select(
				'id',
				'is_nsfw',
				'profile_id',
				'type',
				'uri',
			  )
			  ->whereNull('uri')
			  ->whereType('photo')
			  ->whereIsNsfw(false)
			  ->whereVisibility('public')
			  ->where('id', '>', $min_id)
			  ->inRandomOrder()
			  ->take(300)
			  ->pluck('id');
	}

	public static function getForYou()
	{
		return Cache::remember('pf:services:discover:for-you', 21600, function() {
			return self::getDailyIdPool();
		});
	}
}
