<?php

namespace App\Services;

use App\Models\Status;
use App\Services\Concerns\SortedSetTimeline;
use Illuminate\Support\Facades\Redis;

class NetworkTimelineService
{
    use SortedSetTimeline;

    const CACHE_KEY = 'pf:services:timeline:v2:network';

    public static function get($start = 0, $stop = 10)
    {
        return self::setGet(self::CACHE_KEY, $start, $stop);
    }

    public static function getRankedMaxId($start = null, $limit = 10)
    {
        return self::setRankedMaxId(self::CACHE_KEY, $start, $limit);
    }

    public static function getRankedMinId($end = null, $limit = 10)
    {
        return self::setRankedMinId(self::CACHE_KEY, $end, $limit);
    }

    public static function add($val)
    {
        return self::setAdd(self::CACHE_KEY, $val, self::feedLimit());
    }

    public static function statusScore($statusId)
    {
        return self::scoreForStatus($statusId);
    }

    public static function rem($val)
    {
        return self::setRem(self::CACHE_KEY, $val);
    }

    public static function del($val)
    {
        return self::rem($val);
    }

    public static function count()
    {
        return self::setCount(self::CACHE_KEY);
    }

    public static function deleteByProfileId($profileId)
    {
        self::setDeleteByProfileId(self::CACHE_KEY, $profileId);
    }

    protected static function feedLimit(): int
    {
        return (int) config('instance.timeline.network.cache_dropoff');
    }

    public static function warmCache($force = false, $limit = null)
    {
        if ($limit === null) {
            $limit = self::feedLimit();
        }

        if (self::count() == 0 || $force == true) {
            $hideNsfw = config('instance.hide_nsfw_on_public_feeds');
            Redis::del(self::CACHE_KEY);
            $filteredDomains = collect(InstanceService::getBannedDomains())
                ->merge(InstanceService::getUnlistedDomains())
                ->unique()
                ->values()
                ->toArray();
            // Select by publication time so the survivors match what live
            // insertion keeps: both paths rank by created_at, so an id that is
            // high but old must not outrank a newer post. Overfetch covers rows
            // dropped by the domain filter below.
            $rows = Status::whereNotNull('uri')
                ->whereScope('public')
                ->when($hideNsfw, function ($q, $hideNsfw) {
                    return $q->where('is_nsfw', false);
                })
                ->whereNull('in_reply_to_id')
                ->whereNull('reblog_of_id')
                ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                ->where('created_at', '>', now()->subHours(config('instance.timeline.network.max_hours_old')))
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(max($limit * 2, $limit + 50))
                ->get(['id', 'uri', 'created_at']);

            $added = 0;
            foreach ($rows as $row) {
                if ($added >= $limit) {
                    break;
                }
                $domain = parse_url($row->uri, PHP_URL_HOST);
                if (in_array($domain, $filteredDomains)) {
                    continue;
                }
                $score = $row->created_at ? $row->created_at->timestamp : self::snowflakeScore($row->id);
                Redis::zadd(self::CACHE_KEY, $score, $row->id);
                $added++;
            }

            if ($added === 0) {
                Redis::del(self::CACHE_KEY);

                return 1;
            }

            if ($added > $limit) {
                Redis::zremrangebyrank(self::CACHE_KEY, 0, -($limit + 1));
            }

            return 1;
        }

        return 0;
    }
}
