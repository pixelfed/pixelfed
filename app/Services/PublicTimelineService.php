<?php

namespace App\Services;

use App\Models\Status;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class PublicTimelineService
{
    const CACHE_KEY = 'pf:services:timeline:public';

    public static function get($start = 0, $stop = 10)
    {
        if ($stop > 100) {
            $stop = 100;
        }

        return Redis::zrevrange(self::CACHE_KEY, $start, $stop);
    }

    public static function getRankedMaxId($start = null, $limit = 10)
    {
        if (! $start) {
            return [];
        }

        return array_keys(Redis::zrevrangebyscore(self::CACHE_KEY, '('.$start, '-inf', [
            'withscores' => true,
            'limit' => [0, $limit],
        ]));
    }

    public static function getRankedMinId($end = null, $limit = 10)
    {
        if (! $end) {
            return [];
        }

        return array_keys(Redis::zrevrangebyscore(self::CACHE_KEY, '+inf', '('.$end, [
            'withscores' => true,
            'limit' => [0, $limit],
        ]));
    }

    public static function add($val, bool $evict = true)
    {
        $cap = (int) config('instance.timeline.local.cache_size');
        if ($evict && self::count() >= $cap) {
            Redis::zpopmin(self::CACHE_KEY);
        }

        return Redis::zadd(self::CACHE_KEY, $val, $val);
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
            foreach ($rows as $row) {
                if (AdminShadowFilterService::canAddToPublicFeedByProfileId($row->profile_id)) {
                    self::add($row->id);
                }
            }

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
