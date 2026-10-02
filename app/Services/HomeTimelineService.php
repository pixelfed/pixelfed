<?php

namespace App\Services;

use App\Models\Follower;
use App\Models\Status;
use App\Models\UserDomainBlock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class HomeTimelineService
{
    const CACHE_KEY = 'pf:services:timeline:home:';

    const BUILT_KEY = 'pf:services:timeline:home:builtat:';

    const FOLLOWER_FEED_POST_LIMIT = 10;

    public static function get($id, $start = 0, $stop = 10)
    {
        if ($stop > 100) {
            $stop = 100;
        }

        $res = Redis::zrevrange(self::CACHE_KEY.$id, $start, $stop);
        self::applyTtl($id);

        return $res;
    }

    public static function getRankedMaxId($id, $start = null, $limit = 10)
    {
        if (! $start) {
            return [];
        }

        $res = array_keys(Redis::zrevrangebyscore(self::CACHE_KEY.$id, '('.$start, '-inf', [
            'withscores' => true,
            'limit' => [0, $limit],
        ]));
        self::applyTtl($id);

        return $res;
    }

    public static function getRankedMinId($id, $end = null, $limit = 10)
    {
        if (! $end) {
            return [];
        }

        $res = array_keys(Redis::zrevrangebyscore(self::CACHE_KEY.$id, '+inf', '('.$end, [
            'withscores' => true,
            'limit' => [0, $limit],
        ]));
        self::applyTtl($id);

        return $res;
    }

    /**
     * Refresh the sliding idle expiry on a profile's timeline key while never
     * extending it past the absolute ceiling measured from when the key was
     * built. Effective expiry = min(now + idle, built_at + max). EXPIRE is a
     * no-op on a missing key, so reads never create an empty key just to expire
     * it. A key already past its ceiling is deleted so the next read rebuilds.
     */
    private static function applyTtl(int|string $id): void
    {
        $idle = (int) config('instance.timeline.home.ttl_idle');
        if ($idle <= 0) {
            return;
        }

        $idleSeconds = $idle * 3600;
        $max = (int) config('instance.timeline.home.ttl_max');
        $builtAt = Redis::get(self::BUILT_KEY.$id);

        if ($max <= 0 || $builtAt === null) {
            Redis::expire(self::CACHE_KEY.$id, $idleSeconds);

            return;
        }

        $maxRemaining = ((int) $builtAt + $max * 3600) - now()->timestamp;

        if ($maxRemaining <= 0) {
            Redis::del(self::CACHE_KEY.$id);
            Redis::del(self::BUILT_KEY.$id);

            return;
        }

        Redis::expire(self::CACHE_KEY.$id, min($idleSeconds, $maxRemaining));
    }

    /**
     * Stamp the build time for a profile's timeline key and give the companion
     * marker its own expiry equal to the max ceiling so it self-cleans. Only
     * called on a full rebuild — incremental writes must not reset the clock.
     */
    private static function stampBuiltAt(int|string $id): void
    {
        $max = (int) config('instance.timeline.home.ttl_max');
        if ($max <= 0) {
            return;
        }

        Redis::setex(self::BUILT_KEY.$id, $max * 3600, now()->timestamp);
    }

    // Insert many ids into one profile key in batched round-trips (score = id).
    // Callers either start from a freshly cleared key (warm) or bound the result
    // with trimToBound afterward (backfill), so no per-id eviction is needed.
    public static function bulkAdd($id, array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        foreach (array_chunk($ids, 1000) as $chunk) {
            $args = [];
            foreach ($chunk as $val) {
                $args[] = (int) $val; // score
                $args[] = (int) $val; // member
            }
            Redis::zadd(self::CACHE_KEY.$id, ...$args);
        }

        self::applyTtl($id);
    }

    /**
     * Reduce status rows to an ordered id list, dropping any whose remote host
     * is in the viewer's domain-block list. Local posts (null uri) are never
     * blocked. Resolving the host from the uri column avoids hydrating each row.
     *
     * @param  Collection<int, Status>  $rows
     * @param  array<int, string>  $domainBlocks
     * @return array<int, int>
     */
    private static function rejectBlockedDomains($rows, array $domainBlocks): array
    {
        if (empty($domainBlocks)) {
            return $rows->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        }

        return $rows
            ->reject(function ($row) use ($domainBlocks) {
                if (! $row->uri) {
                    return false;
                }
                $domain = strtolower((string) parse_url($row->uri, PHP_URL_HOST));

                return in_array($domain, $domainBlocks);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public static function add($id, $val, bool $evict = true)
    {
        $cap = (int) config('instance.timeline.home.cache_size');
        if ($evict && self::count($id) >= $cap) {
            Redis::zpopmin(self::CACHE_KEY.$id);
        }

        $res = Redis::zadd(self::CACHE_KEY.$id, $val, $val);
        self::applyTtl($id);

        return $res;
    }

    // Trim the oldest tail so a non-evicting write cannot grow the key past cap + one window.
    public static function trimToBound(int $id, int $window)
    {
        $cap = (int) config('instance.timeline.home.cache_size');
        $count = self::count($id);
        if ($count > $cap + $window) {
            Redis::zremrangebyrank(self::CACHE_KEY.$id, 0, $count - ($cap + $window) - 1);
        }
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
            Redis::del(self::CACHE_KEY.$id);
            $following = Cache::remember('profile:following:'.$id, 1209600, function () use ($id) {
                $following = Follower::whereProfileId($id)->pluck('following_id');

                return $following->push($id)->toArray();
            });

            $minId = SnowflakeService::byDate(now()->subDays((int) config('instance.timeline.home.max_backfill_days')));

            $filters = UserFilterService::filters($id);

            if ($filters && count($filters)) {
                $following = array_diff($following, $filters);
            }

            $domainBlocks = UserDomainBlock::whereProfileId($id)->pluck('domain')->toArray();

            $rows = Status::where('id', '>', $minId)
                ->whereIn('profile_id', $following)
                ->whereNull('in_reply_to_id')
                ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album', 'share'])
                ->whereIn('visibility', ['public', 'unlisted', 'private'])
                ->orderByDesc('id')
                ->limit($limit)
                ->get(['id', 'uri']);

            $ids = self::rejectBlockedDomains($rows, $domainBlocks);

            self::stampBuiltAt($id);
            self::bulkAdd($id, $ids);

            return $returnIds ? collect($ids) : 1;
        }

        return 0;
    }

    // Pull older posts past the cached window straight from the DB and merge them back in.
    public static function backfillOlder(int $pid, int $maxId, int $limit, array $following): array
    {
        $floorId = SnowflakeService::byDate(now()->subDays((int) config('instance.timeline.home.max_backfill_days')));

        if ($maxId <= $floorId) {
            return [];
        }

        $filters = UserFilterService::filters($pid);
        if ($filters && count($filters)) {
            $following = array_diff($following, $filters);
        }

        $domainBlocks = UserDomainBlock::whereProfileId($pid)->pluck('domain')->toArray();

        $rows = Status::where('id', '<', $maxId)
            ->where('id', '>', $floorId)
            ->whereIn('profile_id', $following)
            ->whereNull('in_reply_to_id')
            ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album', 'share'])
            ->whereIn('visibility', ['public', 'unlisted', 'private'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'uri']);

        $ids = self::rejectBlockedDomains($rows, $domainBlocks);

        $lock = Cache::lock("pf:tl:backfill:home:{$pid}:{$maxId}", 10);

        if (! $lock->get()) {
            return $ids;
        }

        try {
            self::bulkAdd($pid, $ids);
            self::trimToBound($pid, $limit);
        } finally {
            $lock->release();
        }

        return $ids;
    }
}
