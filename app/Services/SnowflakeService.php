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

    public static function next(): int
    {
        $key = 'snowflake:seq';
        $seq = Cache::increment($key);
    
        if ($seq > 4095) {
            Cache::put($key, 0);
            $seq = 0;
        }
    
        $datacenterId = (int) (config('snowflake.datacenter_id') ?? random_int(1, 31));
        $workerId     = (int) (config('snowflake.worker_id') ?? random_int(1, 31));
    
        $timestampMs = (int) floor(microtime(true) * 1000) - 1549756800000;
    
        return ($timestampMs << 22)
            | (($datacenterId & 0x1F) << 17)
            | (($workerId & 0x1F) << 12)
            | ($seq & 0xFFF);
    }
}
