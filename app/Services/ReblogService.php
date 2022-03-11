<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Status;

class ReblogService
{
	const CACHE_KEY = 'pf:services:reblogs:';
	const REBLOGS_KEY = 'pf:services:reblogs:v1:post:';
	const COLDBOOT_KEY = 'pf:services:reblogs:v1:post_:';

	public static function get($profileId, $statusId)
	{
		if (!Redis::zcard(self::CACHE_KEY . $profileId)) {
			return false;
		}

		return Redis::zscore(self::CACHE_KEY . $profileId, $statusId) != null;
	}

	public static function add($profileId, $statusId)
	{
		return Redis::zadd(self::CACHE_KEY . $profileId, $statusId, $statusId);
	}

	public static function del($profileId, $statusId)
	{
		return Redis::zrem(self::CACHE_KEY . $profileId, $statusId);
	}

	public static function getPostReblogs($id, $start = 0, $stop = 10)
	{
		if(!Redis::zcard(self::REBLOGS_KEY . $id)) {
			return Cache::remember(self::COLDBOOT_KEY . $id, 86400, function() use($id) {
				return Status::whereReblogOfId($id)
					->pluck('id')
					->each(function($reblog) use($id) {
						self::addPostReblog($id, $reblog);
					})
					->map(function($reblog) {
						return (string) $reblog;
					});
			});
		}
		return Redis::zrange(self::REBLOGS_KEY . $id, $start, $stop);
	}

	public static function addPostReblog($parentId, $reblogId)
	{
		$pid = intval($parentId);
		$id = intval($reblogId);
		if($pid && $id) {
			return Redis::zadd(self::REBLOGS_KEY . $pid, $id, $id);
		}
	}

	public static function removePostReblog($parentId, $reblogId)
	{
		$pid = intval($parentId);
		$id = intval($reblogId);
		if($pid && $id) {
			return Redis::zrem(self::REBLOGS_KEY . $pid, $id);
		}
	}
}
