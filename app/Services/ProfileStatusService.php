<?php

namespace App\Services;

use DB;
use Illuminate\Support\Facades\Redis;

class ProfileStatusService
{
    const CACHE_KEY = 'pf:services:profile-statuses:ids:';
    const COLD_CHECK_KEY = 'pf:services:profile-statuses:id-ttl:';
    const FALLOFF_LIMIT = 40;

    public static function get($id, $start = 0, $stop = 8)
    {
        $key = self::CACHE_KEY . $id;
        if(!Redis::zscore(self::COLD_CHECK_KEY, $id)) {
            $res = self::coldFetch($id);
            if($res && count($res)) {
                return array_slice($res, $start, $stop);
            }
        }
        $ids = Redis::zrevrange($key, $start, $stop - 1);
        return $ids;
    }

    public static function count($id)
    {
        return Redis::zcount(self::CACHE_KEY . $id, '-inf', '+inf');
    }

    public static function add($pid, $sid)
    {
        if(self::count($pid) > self::FALLOFF_LIMIT) {
            Redis::zpopmin(self::CACHE_KEY . $pid);
        }
        return Redis::zadd(self::CACHE_KEY . $pid, $sid, $sid);
    }

    public static function delete($pid, $sid)
    {
        return Redis::zrem(self::CACHE_KEY . $pid, $sid);
    }

    public static function coldFetch($pid)
    {
        Redis::del(self::CACHE_KEY . $pid);
        $ids = DB::table('statuses')
            ->select('id', 'profile_id', 'type', 'scope')
            ->whereIn('type', ['photo', 'photo:album', 'video'])
            ->whereIn('scope', ['public', 'unlisted'])
            ->whereProfileId($pid)
            ->orderByDesc('id')
            ->limit(self::FALLOFF_LIMIT)
            ->pluck('id')
            ->toArray();

        if($ids && count($ids)) {
            foreach($ids as $id) {
                self::add($pid, $id);
            }
        }
        Redis::zadd(self::COLD_CHECK_KEY, $pid, $pid);
        return $ids;
    }
}
