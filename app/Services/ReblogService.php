<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class ReblogService
{
	const CACHE_KEY = 'pf:services:reblogs:';

	public static function get($profileId, $statusId)
	{
		if (!Redis::zcard(self::CACHE_KEY . $profileId)) {
			return false;
		}

		return Redis::zscore(self::CACHE_KEY . $profileId, $statusId) != null;
	}

	public static function add($profileId, $statusId)
	{
		return Redis::zadd(self::CACHE_KEY . $profileId, $statusId, $statusId);
	}

	public static function del($profileId, $statusId)
	{
		return Redis::zrem(self::CACHE_KEY . $profileId, $statusId);
	}
}
