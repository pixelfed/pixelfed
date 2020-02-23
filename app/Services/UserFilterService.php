<?php

namespace App\Services;

use Cache;
use Illuminate\Support\Facades\Redis;

use App\Follower;
use App\Profile;
use App\UserFilter;

class UserFilterService {

	const USER_MUTES_KEY = 'pf:services:mutes:ids:';
	const USER_BLOCKS_KEY = 'pf:services:blocks:ids:';

	public static function mutes(int $profile_id) : array
	{
		$key = self::USER_MUTES_KEY . $profile_id;
		$cached = Redis::zrevrange($key, 0, -1);
		if($cached) {
			return $cached;
		} else {
			$ids = UserFilter::whereFilterType('mute')
				->whereUserId($profile_id)
				->pluck('filterable_id')
				->toArray();
			foreach ($ids as $muted_id) {
				Redis::zadd($key, (int) $muted_id, (int) $muted_id);
			}
			return $ids;
		}
	}

	public static function blocks(int $profile_id) : array
	{
		$key = self::USER_BLOCKS_KEY . $profile_id;
		$cached = Redis::zrevrange($key, 0, -1);
		if($cached) {
			return $cached;
		} else {
			$ids = UserFilter::whereFilterType('block')
				->whereUserId($profile_id)
				->pluck('filterable_id')
				->toArray();
			foreach ($ids as $blocked_id) {
				Redis::zadd($key, $blocked_id, $blocked_id);
			}
			return $ids;
		}
	}

	public static function filters(int $profile_id) : array
	{
		return array_merge(self::mutes($profile_id), self::blocks($profile_id));
	}

	public static function mute(int $profile_id, int $muted_id)
	{
		$key = self::USER_MUTES_KEY . $profile_id;
		$mutes = self::mutes($profile_id);
		$exists = in_array($muted_id, $mutes);
		if(!$exists) {
			Redis::zadd($key, $muted_id, $muted_id);
		}
		return true;
	}

	public static function unmute(int $profile_id, string $muted_id)
	{
		$key = self::USER_MUTES_KEY . $profile_id;
		$mutes = self::mutes($profile_id);
		$exists = in_array($muted_id, $mutes);
		if($exists) {
			Redis::zrem($key, $muted_id);
		}
		return true;
	}

	public static function block(int $profile_id, int $blocked_id)
	{
		$key = self::USER_BLOCKS_KEY . $profile_id;
		$exists = in_array($blocked_id, self::blocks($profile_id));
		if(!$exists) {
			Redis::zadd($key, $blocked_id, $blocked_id);
		}
		return true;
	}

	public static function unblock(int $profile_id, string $blocked_id)
	{
		$key = self::USER_BLOCKS_KEY . $profile_id;
		$exists = in_array($blocked_id, self::blocks($profile_id));
		if($exists) {
			Redis::zrem($key, $blocked_id);
		}
		return $exists;
	}
}