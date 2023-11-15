<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Hashtag;
use App\StatusHashtag;
use App\HashtagFollow;

class HashtagFollowService
{
	const FOLLOW_KEY = 'pf:services:hashtag-follows:v1:';
	const CACHE_KEY = 'pf:services:hfs:byHid:';
	const CACHE_WARMED = 'pf:services:hfs:wc:byHid';

	public static function getPidByHid($hid)
	{
		if(!self::isWarm($hid)) {
			return self::warmCache($hid);
		}
		return self::get($hid);
	}

	public static function unfollow($hid, $pid)
	{
		return Redis::zrem(self::CACHE_KEY . $hid, $pid);
	}

	public static function add($hid, $pid)
	{
		return Redis::zadd(self::CACHE_KEY . $hid, $pid, $pid);
	}

	public static function rem($hid, $pid)
	{
		return Redis::zrem(self::CACHE_KEY . $hid, $pid);
	}

	public static function get($hid)
	{
		return Redis::zrange(self::CACHE_KEY . $hid, 0, -1);
	}

	public static function count($hid)
	{
		return Redis::zcard(self::CACHE_KEY . $hid);
	}

	public static function warmCache($hid)
	{
		foreach(HashtagFollow::whereHashtagId($hid)->lazyById(20, 'id') as $h) {
			if($h) {
				self::add($h->hashtag_id, $h->profile_id);
			}
		}

		self::setWarm($hid);

		return self::get($hid);
	}

	public static function isWarm($hid)
	{
		return Redis::zcount(self::CACHE_KEY . $hid, 0, -1) ?? Redis::zscore(self::CACHE_WARMED, $hid) != null;
	}

	public static function setWarm($hid)
	{
		return Redis::zadd(self::CACHE_WARMED, $hid, $hid);
	}
}
