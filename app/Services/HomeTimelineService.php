<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Follower;
use App\Status;

class HomeTimelineService
{
    const CACHE_KEY = 'pf:services:timeline:home:';
    const FOLLOWER_FEED_POST_LIMIT = 10;

    public static function get($id, $start = 0, $stop = 10)
    {
        if($stop > 100) {
            $stop = 100;
        }

        return Redis::zrevrange(self::CACHE_KEY . $id, $start, $stop);
    }

    public static function getRankedMaxId($id, $start = null, $limit = 10)
    {
        if(!$start) {
            return [];
        }

        return array_keys(Redis::zrevrangebyscore(self::CACHE_KEY . $id, $start, '-inf', [
            'withscores' => true,
            'limit' => [1, $limit - 1]
        ]));
    }

    public static function getRankedMinId($id, $end = null, $limit = 10)
    {
        if(!$end) {
            return [];
        }

        return array_keys(Redis::zrevrangebyscore(self::CACHE_KEY . $id, '+inf', $end, [
            'withscores' => true,
            'limit' => [0, $limit]
        ]));
    }

    public static function add($id, $val)
    {
        if(self::count($id) >= 400) {
            Redis::zpopmin(self::CACHE_KEY . $id);
        }

        return Redis::zadd(self::CACHE_KEY .$id, $val, $val);
    }

    public static function rem($id, $val)
    {
        return Redis::zrem(self::CACHE_KEY . $id, $val);
    }

    public static function count($id)
    {
        return Redis::zcard(self::CACHE_KEY . $id);
    }

    public static function warmCache($id, $force = false, $limit = 100, $returnIds = false)
    {
        if(self::count($id) == 0 || $force == true) {
            Redis::del(self::CACHE_KEY . $id);
            $following = Cache::remember('profile:following:'.$id, 1209600, function() use($id) {
                $following = Follower::whereProfileId($id)->pluck('following_id');
                return $following->push($id)->toArray();
            });

            $minId = SnowflakeService::byDate(now()->subMonths(6));

            $filters = UserFilterService::filters($id);

            if($filters && count($filters)) {
                $following = array_diff($following, $filters);
            }

            $ids = Status::where('id', '>', $minId)
                ->whereIn('profile_id', $following)
                ->whereNull(['in_reply_to_id', 'reblog_of_id'])
                ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                ->whereIn('visibility',['public', 'unlisted', 'private'])
                ->orderByDesc('id')
                ->limit($limit)
                ->pluck('id');

            foreach($ids as $pid) {
                self::add($id, $pid);
            }

            return $returnIds ? $ids : 1;
        }
        return 0;
    }
}
