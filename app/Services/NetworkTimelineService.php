<?php

namespace App\Services;

use App\Models\Status;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class NetworkTimelineService
{
    const CACHE_KEY = 'pf:services:timeline:network';

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
        $cap = (int) config('instance.timeline.network.cache_dropoff');
        if ($evict && self::count() >= $cap) {
            Redis::zpopmin(self::CACHE_KEY);
        }

        return Redis::zadd(self::CACHE_KEY, $val, $val);
    }

    // Trim the oldest tail so a non-evicting write cannot grow the key past cap + one window.
    public static function trimToBound(int $window)
    {
        $cap = (int) config('instance.timeline.network.cache_dropoff');
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
            $filteredDomains = collect(InstanceService::getBannedDomains())
                ->merge(InstanceService::getUnlistedDomains())
                ->unique()
                ->values()
                ->toArray();
            $ids = Status::whereNotNull('uri')
                ->whereScope('public')
                ->when($hideNsfw, function ($q, $hideNsfw) {
                    return $q->where('is_nsfw', false);
                })
                ->whereNull('in_reply_to_id')
                ->whereNull('reblog_of_id')
                ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                ->where('created_at', '>', now()->subHours(config('instance.timeline.network.max_hours_old')))
                ->orderByDesc('id')
                ->limit($limit)
                ->pluck('uri', 'id');
            $ids = $ids->filter(function ($k, $v) use ($filteredDomains) {
                $domain = parse_url($k, PHP_URL_HOST);

                return ! in_array($domain, $filteredDomains);
            })->map(function ($k, $v) {
                return $v;
            })->flatten();
            foreach ($ids as $id) {
                self::add($id);
            }

            return 1;
        }

        return 0;
    }

    // Pull older posts past the cached window from the DB, paging by id to match the ZSET score.
    public static function backfillOlder(int $maxId, int $limit): array
    {
        $floorId = SnowflakeService::byDate(now()->subHours((int) config('instance.timeline.network.max_hours_old')));

        if ($maxId <= $floorId) {
            return [];
        }

        $hideNsfw = config('instance.hide_nsfw_on_public_feeds');

        $filteredDomains = collect(InstanceService::getBannedDomains())
            ->merge(InstanceService::getUnlistedDomains())
            ->unique()
            ->values()
            ->toArray();

        $ids = Status::where('id', '<', $maxId)
            ->where('id', '>', $floorId)
            ->whereNotNull('uri')
            ->whereScope('public')
            ->when($hideNsfw, function ($q, $hideNsfw) {
                return $q->where('is_nsfw', false);
            })
            ->whereNull('in_reply_to_id')
            ->whereNull('reblog_of_id')
            ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
            ->where('created_at', '>', now()->subHours(config('instance.timeline.network.max_hours_old')))
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('uri', 'id')
            ->reject(function ($uri) use ($filteredDomains) {
                return in_array(parse_url($uri, PHP_URL_HOST), $filteredDomains);
            });

        $statusIds = $ids->keys()->map(fn ($id) => (int) $id)->values()->all();

        $lock = Cache::lock("pf:tl:backfill:network:{$maxId}", 10);

        if (! $lock->get()) {
            return $statusIds;
        }

        try {
            foreach ($statusIds as $id) {
                self::add($id, evict: false);
            }
            self::trimToBound($limit);
        } finally {
            $lock->release();
        }

        return $statusIds;
    }
}
