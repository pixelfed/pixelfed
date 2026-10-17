<?php

namespace App\Services;

use App\Models\Follower;
use App\Models\Status;
use App\Models\UserDomainBlock;
use App\Services\Concerns\SortedSetTimeline;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class HomeTimelineService
{
    use SortedSetTimeline;

    const CACHE_KEY = 'pf:services:timeline:home:';

    const CACHE_TTL = 1209600;

    const FEED_LIMIT = 400;

    const FOLLOWER_FEED_POST_LIMIT = 10;

    public static function get($id, $start = 0, $stop = 10)
    {
        self::cacheKeyTouch($id);

        return self::setGet(self::CACHE_KEY.$id, $start, $stop);
    }

    public static function getRankedMaxId($id, $start = null, $limit = 10)
    {
        self::cacheKeyTouch($id);

        return self::setRankedMaxId(self::CACHE_KEY.$id, $start, $limit);
    }

    public static function getRankedMinId($id, $end = null, $limit = 10)
    {
        self::cacheKeyTouch($id);

        return self::setRankedMinId(self::CACHE_KEY.$id, $end, $limit);
    }

    public static function add($id, $val)
    {
        $res = self::setAdd(self::CACHE_KEY.$id, $val, self::FEED_LIMIT);

        self::cacheKeyTouch($id);

        return $res;
    }

    public static function rem($id, $val)
    {
        return self::setRem(self::CACHE_KEY.$id, $val);
    }

    public static function count($id)
    {
        return self::setCount(self::CACHE_KEY.$id);
    }

    public static function statusScore($statusId)
    {
        return self::scoreForStatus($statusId);
    }

    public static function cacheKeyExists($id)
    {
        return (bool) Redis::exists(self::CACHE_KEY.$id);
    }

    public static function cacheKeyTouch($id)
    {
        if (Redis::exists(self::CACHE_KEY.$id)) {
            Redis::expire(self::CACHE_KEY.$id, self::CACHE_TTL);
        }
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

            $baseQuery = function () use ($following, $minId) {
                return Status::where('id', '>', $minId)
                    ->whereIn('profile_id', $following)
                    ->whereNull(['in_reply_to_id', 'reblog_of_id'])
                    ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                    ->whereIn('visibility', ['public', 'unlisted', 'private']);
            };

            $latestPerAccount = $baseQuery()
                ->select(DB::raw('max(id) as id'))
                ->groupBy('profile_id')
                ->pluck('id')
                ->sortDesc()
                ->take($limit)
                ->values();

            $remaining = $limit - $latestPerAccount->count();

            $recentIds = $remaining > 0
                ? $baseQuery()
                    ->whereNotIn('id', $latestPerAccount->all())
                    ->orderByDesc('id')
                    ->limit($remaining)
                    ->pluck('id')
                : collect();

            $ids = $latestPerAccount
                ->merge($recentIds)
                ->unique()
                ->values();

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
                $score = ! empty($status['created_at']) ? strtotime($status['created_at']) : false;
                Redis::zadd($tmpKey, $score !== false ? $score : $sid, $sid);
                $added++;
            }

            if ($added === 0) {
                Redis::del(self::CACHE_KEY.$id);

                return $returnIds ? $ids : 1;
            }

            if ($added > self::FEED_LIMIT) {
                Redis::zremrangebyrank($tmpKey, 0, -(self::FEED_LIMIT + 1));
            }

            Redis::rename($tmpKey, self::CACHE_KEY.$id);
            Redis::expire(self::CACHE_KEY.$id, self::CACHE_TTL);

            return $returnIds ? $ids : 1;
        }

        return 0;
    }
}
