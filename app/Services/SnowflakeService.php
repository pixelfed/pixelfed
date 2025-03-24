<?php

namespace App\Services;

use Cache;
use Illuminate\Support\Carbon;

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
        $seq = Cache::get('snowflake:seq');

        if (! $seq) {
            Cache::put('snowflake:seq', 1);
            $seq = 1;
        } else {
            Cache::increment('snowflake:seq');
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
