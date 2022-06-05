<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Cache;
use DB;
use App\{
	Follower,
	Profile,
	User
};

class FollowerService
{
	const CACHE_KEY = 'pf:services:followers:';
	const FOLLOWING_KEY = 'pf:services:follow:following:id:';
	const FOLLOWERS_KEY = 'pf:services:follow:followers:id:';

	public static function add($actor, $target)
	{
		RelationshipService::refresh($actor, $target);
		Redis::zadd(self::FOLLOWING_KEY . $actor, $target, $target);
		Redis::zadd(self::FOLLOWERS_KEY . $target, $actor, $actor);
	}

	public static function remove($actor, $target)
	{
		RelationshipService::refresh($actor, $target);
		Redis::zrem(self::FOLLOWING_KEY . $actor, $target);
		Redis::zrem(self::FOLLOWERS_KEY . $target, $actor);
		Cache::forget('pf:services:follow:audience:' . $actor);
		Cache::forget('pf:services:follow:audience:' . $target);
	}

	public static function followers($id, $start = 0, $stop = 10)
	{
		return Redis::zrange(self::FOLLOWERS_KEY . $id, $start, $stop);
	}

	public static function following($id, $start = 0, $stop = 10)
	{
		return Redis::zrange(self::FOLLOWING_KEY . $id, $start, $stop);
	}

	public static function follows(string $actor, string $target)
	{
		return Follower::whereProfileId($actor)->whereFollowingId($target)->exists();
	}

	public static function audience($profile, $scope = null)
	{
		return (new self)->getAudienceInboxes($profile, $scope);
	}

	public static function softwareAudience($profile, $software = 'pixelfed')
	{
		return collect(self::audience($profile))
			->filter(function($inbox) use($software) {
				$domain = parse_url($inbox, PHP_URL_HOST);
				if(!$domain) {
					return false;
				}
				return InstanceService::software($domain) === strtolower($software);
			})
			->unique()
			->values()
			->toArray();
	}

	protected function getAudienceInboxes($pid, $scope = null)
	{
		$key = 'pf:services:follow:audience:' . $pid;
		return Cache::remember($key, 86400, function() use($pid) {
			$profile = Profile::find($pid);
			if(!$profile) {
				return [];
			}
			return $profile
				->followers()
				->get()
				->map(function($follow) {
					return $follow->sharedInbox ?? $follow->inbox_url;
				})
				->filter()
				->unique()
				->values()
				->toArray();
		});
	}

	public static function mutualCount($pid, $mid)
	{
		return Cache::remember(self::CACHE_KEY . ':mutualcount:' . $pid . ':' . $mid, 3600, function() use($pid, $mid) {
			return DB::table('followers as u')
				->join('followers as s', 'u.following_id', '=', 's.following_id')
				->where('s.profile_id', $mid)
				->where('u.profile_id', $pid)
				->count();
		});
	}

	public static function mutualIds($pid, $mid, $limit = 3)
	{
		$key = self::CACHE_KEY . ':mutualids:' . $pid . ':' . $mid . ':limit_' . $limit;
		return Cache::remember($key, 3600, function() use($pid, $mid, $limit) {
			return DB::table('followers as u')
				->join('followers as s', 'u.following_id', '=', 's.following_id')
				->where('s.profile_id', $mid)
				->where('u.profile_id', $pid)
				->limit($limit)
				->pluck('s.following_id')
				->toArray();
		});
	}

}
