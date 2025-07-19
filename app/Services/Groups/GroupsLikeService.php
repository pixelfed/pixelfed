<?php

namespace App\Services\Groups;

use App\Models\GroupLike;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class GroupsLikeService
{
    const CACHE_KEY = 'pf:services:group-likes:ids:';

    const CACHE_SET_KEY = 'pf:services:group-likes:set:';

    const CACHE_POST_KEY = 'pf:services:group-likes:count:';

    public static function add($profileId, $statusId)
    {
        $key = self::CACHE_KEY.$profileId.':'.$statusId;
        Cache::increment(self::CACHE_POST_KEY.$statusId);
        // Cache::forget('pf:services:likes:liked_by:'.$statusId);
        self::setAdd($profileId, $statusId);

        return Cache::put($key, true, 86400);
    }

    public static function setAdd($profileId, $statusId)
    {
        if (self::setCount($profileId) > 400) {
            Redis::zpopmin(self::CACHE_SET_KEY.$profileId);
        }

        return Redis::zadd(self::CACHE_SET_KEY.$profileId, $statusId, $statusId);
    }

    public static function setCount($id)
    {
        return Redis::zcard(self::CACHE_SET_KEY.$id);
    }

    public static function setRem($profileId, $val)
    {
        return Redis::zrem(self::CACHE_SET_KEY.$profileId, $val);
    }

    public static function get($profileId, $start = 0, $stop = 10)
    {
        if ($stop > 100) {
            $stop = 100;
        }

        return Redis::zrevrange(self::CACHE_SET_KEY.$profileId, $start, $stop);
    }

    public static function remove($profileId, $statusId)
    {
        $key = self::CACHE_KEY.$profileId.':'.$statusId;
        Cache::decrement(self::CACHE_POST_KEY.$statusId);
        // Cache::forget('pf:services:likes:liked_by:'.$statusId);
        self::setRem($profileId, $statusId);

        return Cache::put($key, false, 86400);
    }

    public static function liked($profileId, $statusId)
    {
        $key = self::CACHE_KEY.$profileId.':'.$statusId;

        return Cache::remember($key, 900, function () use ($profileId, $statusId) {
            return GroupLike::whereProfileId($profileId)->whereStatusId($statusId)->exists();
        });
    }

    public static function likedBy($status)
    {
        $empty = [
            'username' => null,
            'others' => false,
        ];

        return $empty;
    }

    public static function count($id)
    {
        return Cache::get(self::CACHE_POST_KEY.$id, 0);
    }
}
