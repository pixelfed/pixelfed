<?php

namespace App\Services;

use App\Bookmark;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class BookmarkService
{
	const CACHE_KEY = 'pf:services:bookmarks:';

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
