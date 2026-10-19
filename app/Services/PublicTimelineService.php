<?php

namespace App\Services;

use App\Models\Status;
use App\Services\Concerns\SortedSetTimeline;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class PublicTimelineService
{
    use SortedSetTimeline;

    const CACHE_KEY = 'pf:services:timeline:v2:public';

    const FEED_LIMIT = 400;

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
        return self::setAdd(self::CACHE_KEY, $val, self::FEED_LIMIT);
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

    public static function warmCache($force = false, $limit = 100)
    {
        if (self::count() == 0 || $force == true) {
            $baseline = self::rebuildBaseline(self::CACHE_KEY);
            $hideNsfw = config('instance.hide_nsfw_on_public_feeds');
            $minId = SnowflakeService::byDate(now()->subDays(90));
            $rows = Status::where('id', '>', $minId)
                ->whereNull(['uri', 'in_reply_to_id', 'reblog_of_id'])
                ->when($hideNsfw, function ($q, $hideNsfw) {
                    return $q->where('is_nsfw', false);
                })
                ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                ->whereScope('public')
                ->orderByDesc('id')
                ->limit($limit)
                ->get(['id', 'profile_id', 'created_at']);

            self::purgeRebuildKeys(self::CACHE_KEY);
            $tmpKey = self::CACHE_KEY.':rebuild:'.Str::random(8);
            $added = 0;
            foreach ($rows as $row) {
                if (AdminShadowFilterService::canAddToPublicFeedByProfileId($row->profile_id)) {
                    $score = $row->created_at ? $row->created_at->timestamp : self::snowflakeScore($row->id);
                    Redis::zadd($tmpKey, $score, $row->id);
                    if ($added === 0) {
                        Redis::expire($tmpKey, self::rebuildKeyTtl());
                    }
                    $added++;
                }
            }

            if ($added === 0) {
                Redis::del(self::CACHE_KEY);

                return 1;
            }

            if ($added > self::FEED_LIMIT) {
                Redis::zremrangebyrank($tmpKey, 0, -(self::FEED_LIMIT + 1));
            }

            self::publishRebuild(self::CACHE_KEY, $tmpKey, $baseline);

            return 1;
        }

        return 0;
    }
}
