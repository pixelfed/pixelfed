<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Hashtag;
use App\StatusHashtag;
use App\HashtagFollow;

class HashtagService {

	const FOLLOW_KEY = 'pf:services:hashtag:following:';

	public static function get($id)
	{
		return Cache::remember('services:hashtag:by_id:' . $id, 3600, function() use($id) {
			$tag = Hashtag::find($id);
			if(!$tag) {
				return [];
			}
			return [
				'name' => $tag->name,
				'slug' => $tag->slug,
			];
		});
	}

	public static function count($id)
	{
		return Cache::remember('services:hashtag:count:by_id:' . $id, 3600, function() use($id) {
			return StatusHashtag::whereHashtagId($id)->count();
		});
	}

	public static function isFollowing($pid, $hid)
	{
		$res = Redis::zscore(self::FOLLOW_KEY . $pid, $hid);
		if($res) {
			return true;
		}

		$synced = Cache::get(self::FOLLOW_KEY . $pid . ':synced');
		if(!$synced) {
			$tags = HashtagFollow::whereProfileId($pid)
				->get()
				->each(function($tag) use($pid) {
					self::follow($pid, $tag->hashtag_id);
				});
			Cache::set(self::FOLLOW_KEY . $pid . ':synced', true, 1209600);

			return (bool) Redis::zscore(self::FOLLOW_KEY . $pid, $hid) > 1;
		}

		return false;
	}

	public static function follow($pid, $hid)
	{
		return Redis::zadd(self::FOLLOW_KEY . $pid, $hid, $hid);
	}

	public static function unfollow($pid, $hid)
	{
		return Redis::zrem(self::FOLLOW_KEY . $pid, $hid);
	}
}
