<?php

namespace App\Services;

use App\Models\Follower;
use App\Models\Status;
use App\Models\UserDomainBlock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class HomeTimelineService
{
    const CACHE_KEY = 'pf:services:timeline:home:';

    const FOLLOWER_FEED_POST_LIMIT = 10;

    public static function get($id, $start = 0, $stop = 10)
    {
        if ($stop > 100) {
            $stop = 100;
        }

        return Redis::zrevrange(self::CACHE_KEY.$id, $start, $stop);
    }

    public static function getRankedMaxId($id, $start = null, $limit = 10)
    {
        if (! $start) {
            return [];
        }

        return array_keys(Redis::zrevrangebyscore(self::CACHE_KEY.$id, '('.$start, '-inf', [
            'withscores' => true,
            'limit' => [0, $limit],
        ]));
    }

    public static function getRankedMinId($id, $end = null, $limit = 10)
    {
        if (! $end) {
            return [];
        }

        return array_keys(Redis::zrevrangebyscore(self::CACHE_KEY.$id, '+inf', '('.$end, [
            'withscores' => true,
            'limit' => [0, $limit],
        ]));
    }

    public static function add($id, $val)
    {
        $res = Redis::zadd(self::CACHE_KEY.$id, $val, $val);

        if (self::count($id) > 400) {
            Redis::zremrangebyrank(self::CACHE_KEY.$id, 0, -401);
        }

        return $res;
    }

    public static function rem($id, $val)
    {
        return Redis::zrem(self::CACHE_KEY.$id, $val);
    }

    public static function count($id)
    {
        return Redis::zcard(self::CACHE_KEY.$id);
    }

    public static function warmCache($id, $force = false, $limit = 100, $returnIds = false)
    {
        if (self::count($id) == 0 || $force == true) {
            $following = Cache::remember('profile:following:'.$id, 1209600, function () use ($id) {
                $following = Follower::whereProfileId($id)->pluck('following_id');

                return $following->push($id)->toArray();
            });

            $minId = SnowflakeService::byDate(now()->subMonths(6));

            $filters = UserFilterService::filters($id);

            if ($filters && count($filters)) {
                $following = array_diff($following, $filters);
            }

            $domainBlocks = UserDomainBlock::whereProfileId($id)->pluck('domain')->toArray();

            $ids = Status::where('id', '>', $minId)
                ->whereIn('profile_id', $following)
                ->whereNull(['in_reply_to_id', 'reblog_of_id'])
                ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                ->whereIn('visibility', ['public', 'unlisted', 'private'])
                ->orderByDesc('id')
                ->limit($limit)
                ->pluck('id');

            $tmpKey = self::CACHE_KEY.$id.':rebuild:'.Str::random(8);
            $added = 0;
            foreach ($ids as $sid) {
                $status = StatusService::get($sid, false);
                if (! $status || ! isset($status['account'], $status['url'])) {
                    continue;
                }
                if ($domainBlocks && count($domainBlocks)) {
                    $domain = strtolower(parse_url($status['url'], PHP_URL_HOST));
                    if (in_array($domain, $domainBlocks)) {
                        continue;
                    }
                }
                Redis::zadd($tmpKey, $sid, $sid);
                $added++;
            }

            if ($added === 0) {
                Redis::del(self::CACHE_KEY.$id);

                return $returnIds ? $ids : 1;
            }

            if ($added > 400) {
                Redis::zremrangebyrank($tmpKey, 0, -401);
            }

            Redis::rename($tmpKey, self::CACHE_KEY.$id);

            return $returnIds ? $ids : 1;
        }

        return 0;
    }
}
