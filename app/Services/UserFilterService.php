<?php

namespace App\Services;

use Cache;
use App\UserFilter;
use Illuminate\Support\Facades\Redis;

class UserFilterService
{
	const USER_MUTES_KEY = 'pf:services:mutes:ids:';
	const USER_BLOCKS_KEY = 'pf:services:blocks:ids:';

	public static function mutes(int $profile_id)
	{
		$key = self::USER_MUTES_KEY . $profile_id;
		$warm = Cache::has($key . ':cached');
		if($warm) {
			return Redis::zrevrange($key, 0, -1) ?? [];
		} else {
			if(Redis::zrevrange($key, 0, -1)) {
				return Redis::zrevrange($key, 0, -1);
			}
			$ids = UserFilter::whereFilterType('mute')
				->whereUserId($profile_id)
				->pluck('filterable_id')
				->toArray();
			foreach ($ids as $muted_id) {
				Redis::zadd($key, (int) $muted_id, (int) $muted_id);
			}
			Cache::set($key . ':cached', 1, 7776000);
			return $ids;
		}
	}

	public static function blocks(int $profile_id)
	{
		$key = self::USER_BLOCKS_KEY . $profile_id;
		$warm = Cache::has($key . ':cached');
		if($warm) {
			return Redis::zrevrange($key, 0, -1) ?? [];
		} else {
			if(Redis::zrevrange($key, 0, -1)) {
				return Redis::zrevrange($key, 0, -1);
			}
			$ids = UserFilter::whereFilterType('block')
				->whereUserId($profile_id)
				->pluck('filterable_id')
				->toArray();
			foreach ($ids as $blocked_id) {
				Redis::zadd($key, (int) $blocked_id, (int) $blocked_id);
			}
			Cache::set($key . ':cached', 1, 7776000);
			return $ids;
		}
	}

	public static function filters(int $profile_id)
	{
		return array_unique(array_merge(self::mutes($profile_id), self::blocks($profile_id)));
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

	public static function blockCount(int $profile_id)
	{
		return Redis::zcard(self::USER_BLOCKS_KEY . $profile_id);
	}

	public static function muteCount(int $profile_id)
	{
		return Redis::zcard(self::USER_MUTES_KEY . $profile_id);
	}
}
