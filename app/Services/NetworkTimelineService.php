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
