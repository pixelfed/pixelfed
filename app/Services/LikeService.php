<?php

namespace App\Services;

use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Like;

class LikeService {

	const CACHE_KEY = 'pf:services:likes:ids:';

	public static function add($profileId, $statusId)
	{
		$key = self::CACHE_KEY . $profileId . ':' . $statusId;
		$ttl = now()->addHours(2);
		return Cache::put($key, true, $ttl);
	}

	public static function remove($profileId, $statusId)
	{
		$key = self::CACHE_KEY . $profileId . ':' . $statusId;
		$ttl = now()->addHours(2);
		return Cache::put($key, false, $ttl);
	}

	public static function liked($profileId, $statusId)
	{
		$key = self::CACHE_KEY . $profileId . ':' . $statusId;
		$ttl = now()->addMinutes(30);
		return Cache::remember($key, $ttl, function() use($profileId, $statusId) {
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

		if(!$status->likes_count) {
			return $empty;
		}
		$user = request()->user();

		if($user) {
			$like = Like::whereStatusId($status->id)
			->where('profile_id', '!=', $user->profile_id)
			->first();
		} else {
			$like = Like::whereStatusId($status->id)
			->first();
		}

		if(!$like) {
			return $empty;
		}

		$id = $like->profile_id;

		$profile = ProfileService::get($id);
		$profileUrl = $profile['local'] ? $profile['url'] : '/i/web/profile/_/' . $profile['id'];
		$res = [
			'username' => $profile['username'],
			'url' => $profileUrl,
			'others' => $status->likes_count >= 3,
		];

		if(request()->user() && request()->user()->profile_id == $status->profile_id) {
			$res['total_count'] = ($status->likes_count - 1);
			$res['total_count_pretty'] = number_format($res['total_count']);
		}

		return $res;
	}

	public static function count($id)
	{
		return Like::whereStatusId($id)->count();
	}
}
