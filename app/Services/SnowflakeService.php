<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SnowflakeService
{
    public static function byDate(?Carbon $ts = null)
    {
        if ($ts instanceof Carbon) {
            $ts = now()->parse($ts)->timestamp;
        } else {
            return self::next();
        }

        $datacenterId = config('snowflake.datacenter_id') ?? random_int(1, 31);
        $workerId = config('snowflake.worker_id') ?? random_int(1, 31);

        return ((round($ts * 1000) - 1549756800000) << 22)
        | ($datacenterId << 17)
        | ($workerId << 12)
        | 0;
    }

    public static function next()
    {
        /*
         * Atomically obtain the next sequence value. Cache::increment()
         * returns the post-increment value, so each call gets a distinct seq.
         * A previous version read the value with Cache::get() and only
         * incremented the store, leaving the local $seq stale — the first two
         * calls both used seq=1, so two IDs generated in the same millisecond
         * with the same datacenter/worker collided (UNIQUE violation).
         */
        $seq = Cache::increment('snowflake:seq');

        if (! is_int($seq)) {
            // Cache miss or non-numeric store value: (re)seed the counter.
            Cache::put('snowflake:seq', 1);
            $seq = 1;
        }

        if ($seq >= 4095) {
            Cache::put('snowflake:seq', 0);
            $seq = 0;
        }

        $datacenterId = config('snowflake.datacenter_id') ?? random_int(1, 31);
        $workerId = config('snowflake.worker_id') ?? random_int(1, 31);

        return ((round(microtime(true) * 1000) - 1549756800000) << 22)
        | ($datacenterId << 17)
        | ($workerId << 12)
        | $seq;
    }
}
