<?php

namespace App\Services;

use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Facades\Redis;
use App\Like;

class LikeService {

	const CACHE_KEY = 'pf:services:likes:ids:';

	public static function getUser($profile_id)
	{
		return self::get($profile_id);
	}

	protected static function get($profile_id)
	{
		$key = self::CACHE_KEY . $profile_id;
		if(Redis::zcard($key) == 0) {
			self::warmCache($profile_id);
		} else {
			return Redis::zrevrange($key, 0, 40);
		}
	}

	protected static function set($profile_id, $status_id)
	{
		$key = self::CACHE_KEY . $profile_id;
		Redis::zadd($key, $status_id, $status_id);
	}

	public static function warmCache($profile_id)
	{
		Like::select('id', 'profile_id', 'status_id')
			->whereProfileId($profile_id)
			->latest()
			->get()
			->each(function($like) use ($profile_id) {
				self::set($profile_id, $like->status_id);
			});
	}

	public static function liked($profileId, $statusId)
	{
		$key = self::CACHE_KEY . $profileId;
		return (bool) Redis::zrank($key, $statusId);
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

		if(!$status->likes_count) {
			return $empty;
		}

		$like = Like::whereStatusId($status->id)->first();

		if(!$like) {
			return $empty;
		}

		$id = $like->profile_id;

		$res = [
			'username' => ProfileService::get($id)['username'],
			'others' => $status->likes_count >= 5,
		];

		if(request()->user()->profile_id == $status->profile_id) {
			$res['total_count'] = $status->likes_count;
			$res['total_count_pretty'] = number_format($res['total_count']);
		}

		return $res;
	}
}
