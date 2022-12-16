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
use App\Jobs\FollowPipeline\FollowServiceWarmCache;

class FollowerService
{
	const CACHE_KEY = 'pf:services:followers:';
	const FOLLOWERS_SYNC_ACTIVE = 'pf:services:followers:sync-active:';
	const FOLLOWERS_SYNC_KEY = 'pf:services:followers:sync-followers:';
	const FOLLOWING_SYNC_KEY = 'pf:services:followers:sync-following:';
	const FOLLOWING_KEY = 'pf:services:follow:following:id:';
	const FOLLOWERS_KEY = 'pf:services:follow:followers:id:';

	public static function add($actor, $target)
	{
		$ts = (int) microtime(true);
		RelationshipService::refresh($actor, $target);
		Redis::zadd(self::FOLLOWING_KEY . $actor, $ts, $target);
		Redis::zadd(self::FOLLOWERS_KEY . $target, $ts, $actor);
		Cache::forget('profile:following:' . $actor);
	}

	public static function remove($actor, $target)
	{
		Redis::zrem(self::FOLLOWING_KEY . $actor, $target);
		Redis::zrem(self::FOLLOWERS_KEY . $target, $actor);
		Cache::forget('pf:services:follow:audience:' . $actor);
		Cache::forget('pf:services:follow:audience:' . $target);
		AccountService::del($actor);
		AccountService::del($target);
		RelationshipService::refresh($actor, $target);
		Cache::forget('profile:following:' . $actor);
	}

	public static function followers($id, $start = 0, $stop = 10)
	{
		self::cacheSyncCheck($id, 'followers');
		return Redis::zrevrange(self::FOLLOWERS_KEY . $id, $start, $stop);
	}

	public static function following($id, $start = 0, $stop = 10)
	{
		self::cacheSyncCheck($id, 'following');
		return Redis::zrevrange(self::FOLLOWING_KEY . $id, $start, $stop);
	}

	public static function followersPaginate($id, $page = 1, $limit = 10)
	{
		$start = $page == 1 ? 0 : $page * $limit - $limit;
		$end = $start + ($limit - 1);
		return self::followers($id, $start, $end);
	}

	public static function followingPaginate($id, $page = 1, $limit = 10)
	{
		$start = $page == 1 ? 0 : $page * $limit - $limit;
		$end = $start + ($limit - 1);
		return self::following($id, $start, $end);
	}

	public static function followerCount($id, $warmCache = true)
	{
		if($warmCache) {
			self::cacheSyncCheck($id, 'followers');
		}
		return Redis::zCard(self::FOLLOWERS_KEY . $id);
	}

	public static function followingCount($id, $warmCache = true)
	{
		if($warmCache) {
			self::cacheSyncCheck($id, 'following');
		}
		return Redis::zCard(self::FOLLOWING_KEY . $id);
	}

	public static function follows(string $actor, string $target)
	{
		if($actor == $target) {
			return false;
		}

		if(self::followerCount($target, false) && self::followingCount($actor, false)) {
			self::cacheSyncCheck($target, 'followers');
			return (bool) Redis::zScore(self::FOLLOWERS_KEY . $target, $actor);
		} else {
			self::cacheSyncCheck($target, 'followers');
			self::cacheSyncCheck($actor, 'following');
			return Follower::whereProfileId($actor)->whereFollowingId($target)->exists();
		}
	}

	public static function cacheSyncCheck($id, $scope = 'followers')
	{
		if($scope === 'followers') {
			if(Cache::get(self::FOLLOWERS_SYNC_KEY . $id) != null) {
				return;
			}

			if(Cache::get(self::FOLLOWERS_SYNC_ACTIVE . $id) != null) {
				return;
			}

			FollowServiceWarmCache::dispatch($id)->onQueue('low');
			Cache::put(self::FOLLOWERS_SYNC_ACTIVE . $id, 1, 604800);
		}
		if($scope === 'following') {
			if(Cache::get(self::FOLLOWING_SYNC_KEY . $id) != null) {
				return;
			}

			if(Cache::get(self::FOLLOWERS_SYNC_ACTIVE . $id) != null) {
				return;
			}

			FollowServiceWarmCache::dispatch($id)->onQueue('low');
			Cache::put(self::FOLLOWERS_SYNC_ACTIVE . $id, 1, 604800);
		}
		return;
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

	public static function delCache($id)
	{
		Redis::del(self::CACHE_KEY . $id);
		Redis::del(self::FOLLOWING_KEY . $id);
		Redis::del(self::FOLLOWERS_KEY . $id);
		Cache::forget(self::FOLLOWERS_SYNC_KEY . $id);
		Cache::forget(self::FOLLOWING_SYNC_KEY . $id);
		Cache::forget(self::FOLLOWERS_SYNC_ACTIVE . $id);
	}
}
