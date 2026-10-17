<?php

namespace App\Services;

use App\Models\Status;
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

        $score = self::cursorScore($start);

        if ($score === null) {
            return [];
        }

        return array_keys(Redis::zrevrangebyscore(self::CACHE_KEY, '('.$score, '-inf', [
            'withscores' => true,
            'limit' => [0, $limit],
        ]));
    }

    public static function getRankedMinId($end = null, $limit = 10)
    {
        if (! $end) {
            return [];
        }

        $score = self::cursorScore($end);

        if ($score === null) {
            return [];
        }

        $ids = array_keys(Redis::zrangebyscore(self::CACHE_KEY, '('.$score, '+inf', [
            'withscores' => true,
            'limit' => [0, $limit],
        ]));

        return array_reverse($ids);
    }

    public static function add($val)
    {
        $res = Redis::zadd(self::CACHE_KEY, self::statusScore($val), $val);

        $limit = (int) config('instance.timeline.network.cache_dropoff');
        if (self::count() > $limit) {
            Redis::zremrangebyrank(self::CACHE_KEY, 0, -($limit + 1));
        }

        return $res;
    }

    public static function statusScore($statusId)
    {
        try {
            $status = StatusService::get($statusId, false);

            if ($status && ! empty($status['created_at'])) {
                $ts = strtotime($status['created_at']);
                if ($ts !== false) {
                    return $ts;
                }
            }
        } catch (\Throwable $e) {
        }

        return (int) $statusId;
    }

    protected static function cursorScore($statusId)
    {
        $score = Redis::zscore(self::CACHE_KEY, $statusId);

        if ($score !== null && $score !== false) {
            return (int) $score;
        }

        return self::statusScore($statusId);
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

    public static function warmCache($force = false, $limit = null)
    {
        if ($limit === null) {
            $limit = (int) config('instance.timeline.network.cache_dropoff');
        }

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
}
