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

    const CACHE_KEY = 'pf:services:timeline:v2:home:';

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

    public static function getRankedMaxIdOrRecent($id, $start = null, $limit = 10)
    {
        self::cacheKeyTouch($id);

        return self::setRankedMaxIdOrRecent(self::CACHE_KEY.$id, $start, $limit);
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

    /**
     * Rebuild the cached feed, returning the number of members published.
     *
     * Returns 0 both when nothing was published and when the guard declined
     * to run; the read path only ever forces, so the two cannot collide
     * there, and every other caller treats them the same way.
     */
    public static function warmCache($id, bool $force = false, int $limit = 100): int
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
                try {
                    $status = StatusService::get($sid, false);
                } catch (\Exception) {
                    continue;
                }
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
                Redis::zadd($tmpKey, $score !== false ? $score : self::snowflakeScore($sid), $sid);
                $added++;
            }

            if ($added === 0) {
                // The instance-wide feeds clear themselves when a warm finds
                // nothing, because their content is the instance's content. A
                // per-user feed is different: a warm can come up empty because
                // this user's relationships or the statuses behind their feed
                // could not be resolved, and throwing away a feed that was
                // serving content is how a user ends up with a blank timeline.
                Redis::del($tmpKey);

                return 0;
            }

            if ($added > self::FEED_LIMIT) {
                Redis::zremrangebyrank($tmpKey, 0, -(self::FEED_LIMIT + 1));
            }

            Redis::rename($tmpKey, self::CACHE_KEY.$id);
            Redis::expire(self::CACHE_KEY.$id, self::CACHE_TTL);

            return $added;
        }

        return 0;
    }
}
