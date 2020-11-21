<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use App\Profile;

class SuggestionService {

	const CACHE_KEY = 'pf:services:suggestion:ids';

	public static function get($start = 0, $stop = -1)
	{
		return Redis::zrange(self::CACHE_KEY, $start, $stop);
	}

	public static function set($val)
	{
		return Redis::zadd(self::CACHE_KEY, 1, $val);
	}

	public static function del($val)
	{
		return Redis::zrem(self::CACHE_KEY, $val);
	}

	public static function add($val)
	{
		return self::set($val);
	}

	public static function rem($val)
	{
		return self::del($val);
	}

	public static function count()
	{
		return Redis::zcount(self::CACHE_KEY, '-inf', '+inf');
	}

	public static function warmCache($force = false)
	{
		if(self::count() == 0 || $force == true) {
			$ids = Profile::whereNull('domain')
				->whereIsSuggestable(true)
				->whereIsPrivate(false)
				->whereHas('statuses')
				->pluck('id');
			foreach($ids as $id) {
				self::set($id);
			}
			return 1;
		}
		return 0;
	}
}