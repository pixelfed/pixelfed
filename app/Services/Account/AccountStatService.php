<?php

namespace App\Services\Account;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class AccountStatService
{
    const REFRESH_CACHE_KEY = 'pf:services:accountstats:refresh:daily';

    public static function incrementPostCount($pid)
    {
        return Redis::zadd(self::REFRESH_CACHE_KEY, $pid, $pid);
    }

    public static function decrementPostCount($pid)
    {
        return Redis::zadd(self::REFRESH_CACHE_KEY, $pid, $pid);
    }

    public static function removeFromPostCount($pid)
    {
        return Redis::zrem(self::REFRESH_CACHE_KEY, $pid);
    }

    public static function getAllPostCountIncr($limit = -1)
    {
        return Redis::zrange(self::REFRESH_CACHE_KEY, 0, $limit);
    }
}
