<?php

namespace App\Services;

use App\Models\Follower;
use App\Models\Status;
use App\Models\UserDomainBlock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

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

    public static function add($id, $val, bool $evict = true)
    {
        $cap = (int) config('instance.timeline.home.cache_size');
        if ($evict && self::count($id) >= $cap) {
            Redis::zpopmin(self::CACHE_KEY.$id);
        }

        return Redis::zadd(self::CACHE_KEY.$id, $val, $val);
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

            $ids = Status::where('id', '>', $minId)
                ->whereIn('profile_id', $following)
                ->whereNull('in_reply_to_id')
                ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album', 'share'])
                ->whereIn('visibility', ['public', 'unlisted', 'private'])
                ->orderByDesc('id')
                ->limit($limit)
                ->pluck('id');

            foreach ($ids as $pid) {
                $status = StatusService::get($pid, false);
                if (! $status || ! isset($status['account'], $status['url'])) {
                    continue;
                }
                if ($domainBlocks && count($domainBlocks)) {
                    $domain = strtolower(parse_url($status['url'], PHP_URL_HOST));
                    if (in_array($domain, $domainBlocks)) {
                        continue;
                    }
                }
                self::add($id, $pid);
            }

            return $returnIds ? $ids : 1;
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

        $ids = Status::where('id', '<', $maxId)
            ->where('id', '>', $floorId)
            ->whereIn('profile_id', $following)
            ->whereNull('in_reply_to_id')
            ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album', 'share'])
            ->whereIn('visibility', ['public', 'unlisted', 'private'])
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('id');

        $lock = Cache::lock("pf:tl:backfill:home:{$pid}:{$maxId}", 10);

        if (! $lock->get()) {
            return $ids->map(fn ($id) => (int) $id)->values()->all();
        }

        try {
            foreach ($ids as $id) {
                if ($domainBlocks && count($domainBlocks)) {
                    $status = StatusService::get($id, false);
                    if (! $status || ! isset($status['url'])) {
                        continue;
                    }
                    $domain = strtolower(parse_url($status['url'], PHP_URL_HOST));
                    if (in_array($domain, $domainBlocks)) {
                        continue;
                    }
                }
                self::add($pid, $id, evict: false);
            }
            self::trimToBound($pid, $limit);
        } finally {
            $lock->release();
        }

        return $ids->map(fn ($id) => (int) $id)->values()->all();
    }
}
