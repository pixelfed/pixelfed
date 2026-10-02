<?php

namespace App\Services;

use App\Models\Status;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class PublicTimelineService
{
    const CACHE_KEY = 'pf:services:timeline:public';

    const BUILT_KEY = 'pf:services:timeline:public:builtat';

    public static function get($start = 0, $stop = 10)
    {
        if ($stop > 100) {
            $stop = 100;
        }

        $res = Redis::zrevrange(self::CACHE_KEY, $start, $stop);
        self::applyTtl();

        return $res;
    }

    public static function getRankedMaxId($start = null, $limit = 10)
    {
        if (! $start) {
            return [];
        }

        $res = array_keys(Redis::zrevrangebyscore(self::CACHE_KEY, '('.$start, '-inf', [
            'withscores' => true,
            'limit' => [0, $limit],
        ]));
        self::applyTtl();

        return $res;
    }

    public static function getRankedMinId($end = null, $limit = 10)
    {
        if (! $end) {
            return [];
        }

        $res = array_keys(Redis::zrevrangebyscore(self::CACHE_KEY, '+inf', '('.$end, [
            'withscores' => true,
            'limit' => [0, $limit],
        ]));
        self::applyTtl();

        return $res;
    }

    /**
     * Hold the public timeline key to an absolute ceiling measured from when it
     * was built, never extended by reads. Expiry = built_at + ttl. A key past
     * its ceiling is deleted so the next read cold-boots a rebuild. A key with
     * no build marker (pre-existing or incrementally grown) falls back to
     * ~ttl-from-now until the next full rebuild stamps a real marker. EXPIRE is
     * a no-op on a missing key, so reads never create an empty key to expire it.
     */
    private static function applyTtl(): void
    {
        $ttl = (int) config('instance.timeline.local.ttl');
        if ($ttl <= 0) {
            return;
        }

        $builtAt = Redis::get(self::BUILT_KEY);

        if ($builtAt === null) {
            Redis::expire(self::CACHE_KEY, $ttl * 3600);

            return;
        }

        $remaining = ((int) $builtAt + $ttl * 3600) - now()->timestamp;

        if ($remaining <= 0) {
            Redis::del(self::CACHE_KEY);
            Redis::del(self::BUILT_KEY);

            return;
        }

        Redis::expire(self::CACHE_KEY, $remaining);
    }

    /**
     * Stamp the build time and give the companion marker its own expiry equal to
     * the ceiling so it self-cleans. Only called on a full rebuild — incremental
     * writes must not reset the clock.
     */
    private static function stampBuiltAt(): void
    {
        $ttl = (int) config('instance.timeline.local.ttl');
        if ($ttl <= 0) {
            return;
        }

        Redis::setex(self::BUILT_KEY, $ttl * 3600, now()->timestamp);
    }

    // Insert many ids in one round-trip (score = id). Used by the warm rebuild,
    // which starts from an empty key and never exceeds the cap, so no eviction.
    public static function bulkAdd(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        // Batch into a handful of ZADD calls instead of one per id.
        foreach (array_chunk($ids, 1000) as $chunk) {
            $args = [];
            foreach ($chunk as $id) {
                $args[] = (int) $id; // score
                $args[] = (int) $id; // member
            }
            Redis::zadd(self::CACHE_KEY, ...$args);
        }

        self::applyTtl();
    }

    public static function add($val, bool $evict = true)
    {
        $cap = (int) config('instance.timeline.local.cache_size');
        if ($evict && self::count() >= $cap) {
            Redis::zpopmin(self::CACHE_KEY);
        }

        $res = Redis::zadd(self::CACHE_KEY, $val, $val);
        self::applyTtl();

        return $res;
    }

    // Trim the oldest tail so a non-evicting write cannot grow the key past cap + one window.
    public static function trimToBound(int $window)
    {
        $cap = (int) config('instance.timeline.local.cache_size');
        $count = self::count();
        if ($count > $cap + $window) {
            Redis::zremrangebyrank(self::CACHE_KEY, 0, $count - ($cap + $window) - 1);
        }
    }

    public static function rem($val)
    {
        return Redis::zrem(self::CACHE_KEY, $val);
    }

    public static function del($val)
    {
        return self::rem($val);
    }

    public static function count()
    {
        return Redis::zcard(self::CACHE_KEY);
    }

    public static function deleteByProfileId($profileId)
    {
        $res = Redis::zrange(self::CACHE_KEY, 0, '-1');
        if (! $res) {
            return;
        }
        foreach ($res as $postId) {
            $s = StatusService::get($postId);
            if (! $s) {
                self::rem($postId);

                continue;
            }

            if (! data_get($s, 'account.id')) {
                self::rem($postId);

                continue;
            }

            if ($s['account']['id'] == $profileId) {
                self::rem($postId);
            }
        }
    }

    public static function warmCache($force = false, $limit = 100)
    {
        if (self::count() == 0 || $force == true) {
            $hideNsfw = config('instance.hide_nsfw_on_public_feeds');
            Redis::del(self::CACHE_KEY);
            $minId = SnowflakeService::byDate(now()->subDays((int) config('instance.timeline.local.max_backfill_days')));
            $rows = Status::where('id', '>', $minId)
                ->whereNull(['uri', 'in_reply_to_id', 'reblog_of_id'])
                ->when($hideNsfw, function ($q, $hideNsfw) {
                    return $q->where('is_nsfw', false);
                })
                ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                ->whereScope('public')
                ->orderByDesc('id')
                ->limit($limit)
                ->get(['id', 'profile_id']);

            $hidden = AdminShadowFilterService::getHideFromPublicFeedsList();

            $ids = $rows
                ->reject(fn ($row) => in_array($row->profile_id, $hidden))
                ->pluck('id')
                ->all();

            self::stampBuiltAt();
            self::bulkAdd($ids);

            return 1;
        }

        return 0;
    }

    // Pull older posts past the cached window straight from the DB and merge them back in.
    public static function backfillOlder(int $maxId, int $limit): array
    {
        $floorId = SnowflakeService::byDate(now()->subDays((int) config('instance.timeline.local.max_backfill_days')));

        if ($maxId <= $floorId) {
            return [];
        }

        $hideNsfw = config('instance.hide_nsfw_on_public_feeds');

        $rows = Status::where('id', '<', $maxId)
            ->where('id', '>', $floorId)
            ->whereNull(['uri', 'in_reply_to_id', 'reblog_of_id'])
            ->when($hideNsfw, function ($q, $hideNsfw) {
                return $q->where('is_nsfw', false);
            })
            ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
            ->whereScope('public')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'profile_id']);

        $lock = Cache::lock("pf:tl:backfill:public:{$maxId}", 10);

        if (! $lock->get()) {
            return $rows->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        }

        try {
            foreach ($rows as $row) {
                if (AdminShadowFilterService::canAddToPublicFeedByProfileId($row->profile_id)) {
                    self::add($row->id, evict: false);
                }
            }
            self::trimToBound($limit);
        } finally {
            $lock->release();
        }

        return $rows->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }
}
