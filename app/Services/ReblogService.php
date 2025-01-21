<?php

namespace App\Services;

use App\Status;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Lottery;

class ReblogService
{
    const CACHE_KEY = 'pf:services:reblogs:';

    const REBLOGS_KEY = 'pf:services:reblogs:v1:post:';

    const COLDBOOT_KEY = 'pf:services:reblogs:v1:post_:';

    const CACHE_SKIP_KEY = 'pf:services:reblogs:skip_empty_check:';

    public static function get($profileId, $statusId)
    {
        return Lottery::odds(1, 20)
            ->winner(fn () => self::getFromDatabaseCheck($profileId, $statusId))
            ->loser(fn () => self::getFromRedis($profileId, $statusId))
            ->choose();
    }

    public static function getFromDatabaseCheck($profileId, $statusId)
    {
        if (! Redis::zcard(self::CACHE_KEY.$profileId)) {
            if (Cache::has(self::CACHE_SKIP_KEY.$profileId)) {
                return false;
            } else {
                self::warmCache($profileId);
                sleep(1);

                return self::getFromRedis($profileId, $statusId);
            }
        }

        $minId = SnowflakeService::byDate(now()->subMonths(12));

        if ($minId > $statusId) {
            return Redis::zscore(self::CACHE_KEY.$profileId, $statusId) != null;
        }

        $cachedRes = (bool) Redis::zscore(self::CACHE_KEY.$profileId, $statusId) != null;
        $databaseRes = (bool) self::getFromDatabase($profileId, $statusId);

        if ($cachedRes === $databaseRes) {
            return $cachedRes;
        }

        self::warmCache($profileId);
        sleep(1);

        return self::getFromDatabase($profileId, $statusId);
    }

    public static function getFromRedis($profileId, $statusId)
    {
        if (! Redis::zcard(self::CACHE_KEY.$profileId)) {
            if (Cache::has(self::CACHE_SKIP_KEY.$profileId)) {
                return false;
            } else {
                self::warmCache($profileId);
                sleep(1);

                return self::getFromDatabase($profileId, $statusId);
            }
        }

        return Redis::zscore(self::CACHE_KEY.$profileId, $statusId) != null;
    }

    public static function getFromDatabase($profileId, $statusId)
    {
        return Status::whereProfileId($profileId)
            ->where('reblog_of_id', $statusId)
            ->exists();
    }

    public static function add($profileId, $statusId)
    {
        return Redis::zadd(self::CACHE_KEY.$profileId, $statusId, $statusId);
    }

    public static function count($profileId)
    {
        return Redis::zcard(self::CACHE_KEY.$profileId);
    }

    public static function del($profileId, $statusId)
    {
        return Redis::zrem(self::CACHE_KEY.$profileId, $statusId);
    }

    public static function getWarmCacheCount($profileId)
    {
        $minId = SnowflakeService::byDate(now()->subMonths(12));

        return Status::where('id', '>', $minId)
            ->whereProfileId($profileId)
            ->whereNotNull('reblog_of_id')
            ->count();
    }

    public static function warmCache($profileId)
    {
        Redis::del(self::CACHE_KEY.$profileId);
        $minId = SnowflakeService::byDate(now()->subMonths(12));
        foreach (
            Status::where('id', '>', $minId)
                ->whereProfileId($profileId)
                ->whereNotNull('reblog_of_id')
                ->lazy() as $post
        ) {
            self::add($profileId, $post->reblog_of_id);
        }
        Cache::put(self::CACHE_SKIP_KEY.$profileId, 1, now()->addHours(24));
    }

    public static function getPostReblogs($id, $start = 0, $stop = 10)
    {
        if (! Redis::zcard(self::REBLOGS_KEY.$id)) {
            return Cache::remember(self::COLDBOOT_KEY.$id, 86400, function () use ($id) {
                return Status::whereReblogOfId($id)
                    ->pluck('id')
                    ->each(function ($reblog) use ($id) {
                        self::addPostReblog($id, $reblog);
                    })
                    ->map(function ($reblog) {
                        return (string) $reblog;
                    });
            });
        }

        return Redis::zrange(self::REBLOGS_KEY.$id, $start, $stop);
    }

    public static function addPostReblog($parentId, $reblogId)
    {
        $pid = intval($parentId);
        $id = intval($reblogId);
        if ($pid && $id) {
            return Redis::zadd(self::REBLOGS_KEY.$pid, $id, $id);
        }
    }

    public static function removePostReblog($parentId, $reblogId)
    {
        $pid = intval($parentId);
        $id = intval($reblogId);
        if ($pid && $id) {
            return Redis::zrem(self::REBLOGS_KEY.$pid, $id);
        }
    }
}
