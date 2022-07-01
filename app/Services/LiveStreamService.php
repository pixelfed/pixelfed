<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class LiveStreamService
{
	const CACHE_KEY = 'pf:services:livestream:';

	public static function getComments($id, $start = 0, $stop = 14)
	{
		$key = self::CACHE_KEY . 'chat:' . $id;
		return Redis::lrange($key, $start, $stop);
	}

	public static function addComment($id, $val)
	{
		$key = self::CACHE_KEY . 'chat:' . $id;
		if(config('database.redis.client') === 'phpredis') {
			if(self::commentsCount($id) >= config('livestreaming.comments.max_falloff')) {
				Redis::rpop($key);
			}
		}

		return Redis::lpush($key, $val);
	}

	public static function commentsCount($id)
	{
		$key = self::CACHE_KEY . 'chat:' . $id;
		return Redis::llen($key);
	}

	public static function deleteComment($id, $val)
	{
		$key = self::CACHE_KEY . 'chat:' . $id;
		return Redis::lrem($key, 0, $val);
	}

	public static function clearChat($id)
	{
		$key = self::CACHE_KEY . 'chat:' . $id;
		return Redis::del($key);
	}
}
