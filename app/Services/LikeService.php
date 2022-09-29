<?php

namespace App\Services;

use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Like;

class LikeService {

	const CACHE_KEY = 'pf:services:likes:ids:';
	const CACHE_SET_KEY = 'pf:services:likes:set:';

	public static function add($profileId, $statusId)
	{
		$key = self::CACHE_KEY . $profileId . ':' . $statusId;
		Cache::increment('pf:services:likes:count:'.$statusId);
		Cache::forget('pf:services:likes:liked_by:'.$statusId);
		self::setAdd($profileId, $statusId);
		return Cache::put($key, true, 86400);
	}

	public static function setAdd($profileId, $statusId)
	{
		if(self::setCount($profileId) > 400) {
			if(config('database.redis.client') === 'phpredis') {
				Redis::zpopmin(self::CACHE_SET_KEY . $profileId);
			}
		}

		return Redis::zadd(self::CACHE_SET_KEY . $profileId, $statusId, $statusId);
	}

	public static function setCount($id)
	{
		return Redis::zcard(self::CACHE_SET_KEY . $id);
	}

	public static function setRem($profileId, $val)
	{
		return Redis::zrem(self::CACHE_SET_KEY . $profileId, $val);
	}

	public static function get($profileId, $start = 0, $stop = 10)
	{
		if($stop > 100) {
			$stop = 100;
		}

		return Redis::zrevrange(self::CACHE_SET_KEY . $profileId, $start, $stop);
	}

	public static function remove($profileId, $statusId)
	{
		$key = self::CACHE_KEY . $profileId . ':' . $statusId;
		Cache::decrement('pf:services:likes:count:'.$statusId);
		Cache::forget('pf:services:likes:liked_by:'.$statusId);
		self::setRem($profileId, $statusId);
		return Cache::put($key, false, 86400);
	}

	public static function liked($profileId, $statusId)
	{
		$key = self::CACHE_KEY . $profileId . ':' . $statusId;
		return Cache::remember($key, 86400, function() use($profileId, $statusId) {
			return Like::whereProfileId($profileId)->whereStatusId($statusId)->exists();
		});
	}

	public static function likedBy($status)
	{
		$empty = [
			'username' => null,
			'others' => false
		];

		if(!$status) {
			return $empty;
		}

		$res = Cache::remember('pf:services:likes:liked_by:' . $status->id, 86400, function() use($status, $empty) {
			$like = Like::whereStatusId($status->id)->first();
			if(!$like) {
				return $empty;
			}
			$id = $like->profile_id;
			$profile = ProfileService::get($id);
			$profileUrl = "/i/web/profile/{$profile['id']}";
			$res = [
				'id' => (string) $profile['id'],
				'username' => $profile['username'],
				'url' => $profileUrl,
				'others' => $status->likes_count >= 3,
			];
			return $res;
		});

		if(!isset($res['id']) || !isset($res['url'])) {
			return $empty;
		}

		$res['total_count'] = ($status->likes_count - 1);
		$res['total_count_pretty'] = number_format($res['total_count']);

		return $res;
	}

	public static function count($id)
	{
		return Cache::get('pf:services:likes:count:'.$id, 0);
	}

}
