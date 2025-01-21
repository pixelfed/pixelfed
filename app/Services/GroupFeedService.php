<?php

namespace App\Services;

use App\Models\GroupPost;
use Illuminate\Support\Facades\Redis;

class GroupFeedService
{
    const CACHE_KEY = 'pf:services:groups:feed:';

    const FEED_LIMIT = 400;

    public static function get($gid, $start = 0, $stop = 10)
    {
        if ($stop > 100) {
            $stop = 100;
        }

        return Redis::zrevrange(self::CACHE_KEY.$gid, $start, $stop);
    }

    public static function getRankedMaxId($gid, $start = null, $limit = 10)
    {
        if (! $start) {
            return [];
        }

        return array_keys(Redis::zrevrangebyscore(self::CACHE_KEY.$gid, $start, '-inf', [
            'withscores' => true,
            'limit' => [1, $limit],
        ]));
    }

    public static function getRankedMinId($gid, $end = null, $limit = 10)
    {
        if (! $end) {
            return [];
        }

        return array_keys(Redis::zrevrangebyscore(self::CACHE_KEY.$gid, '+inf', $end, [
            'withscores' => true,
            'limit' => [0, $limit],
        ]));
    }

    public static function add($gid, $val)
    {
        if (self::count($gid) > self::FEED_LIMIT) {
            if (config('database.redis.client') === 'phpredis') {
                Redis::zpopmin(self::CACHE_KEY.$gid);
            }
        }

        return Redis::zadd(self::CACHE_KEY.$gid, $val, $val);
    }

    public static function rem($gid, $val)
    {
        return Redis::zrem(self::CACHE_KEY.$gid, $val);
    }

    public static function del($gid, $val)
    {
        return self::rem($gid, $val);
    }

    public static function count($gid)
    {
        return Redis::zcard(self::CACHE_KEY.$gid);
    }

    public static function warmCache($gid, $force = false, $limit = 100)
    {
        if (self::count($gid) == 0 || $force == true) {
            Redis::del(self::CACHE_KEY.$gid);
            $ids = GroupPost::whereGroupId($gid)
                ->orderByDesc('id')
                ->limit($limit)
                ->pluck('id');
            foreach ($ids as $id) {
                self::add($gid, $id);
            }

            return 1;
        }
    }
}
