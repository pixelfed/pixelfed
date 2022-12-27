<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Hashtag;
use App\StatusHashtag;

class TrendingHashtagService
{
    const CACHE_KEY = 'api:discover:v1.1:trending:hashtags';

    public static function key($k = null)
    {
        return self::CACHE_KEY . $k;
    }

    public static function getBannedHashtags()
    {
        return Cache::remember(self::key(':is_banned'), 1209600, function() {
            return Hashtag::whereIsBanned(true)->pluck('id')->toArray();
        });
    }

    public static function getBannedHashtagNames()
    {
        return Cache::remember(self::key(':is_banned:names'), 1209600, function() {
            return Hashtag::find(self::getBannedHashtags())->pluck('name')->toArray();
        });
    }

    public static function getNonTrendingHashtags()
    {
        return Cache::remember(self::key(':can_trend'), 1209600, function() {
            return Hashtag::whereCanTrend(false)->pluck('id')->toArray();
        });
    }

    public static function getNsfwHashtags()
    {
        return Cache::remember(self::key(':is_nsfw'), 1209600, function() {
            return Hashtag::whereIsNsfw(true)->pluck('id')->toArray();
        });
    }

    public static function getMinRecentId()
    {
        return Cache::remember(self::key('-min-id'), 86400, function() {
            $minId = StatusHashtag::where('created_at', '>', now()->subMinutes(config('trending.hashtags.recency_mins')))->first();
            if(!$minId) {
                return 0;
            }
            return $minId->id;
        });
    }

    public static function getTrending()
    {
        $minId = self::getMinRecentId();

        $skipIds = array_merge(self::getBannedHashtags(), self::getNonTrendingHashtags(), self::getNsfwHashtags());

        return Cache::remember(self::CACHE_KEY, config('trending.hashtags.ttl'), function() use($minId, $skipIds) {
            return StatusHashtag::select('hashtag_id', \DB::raw('count(*) as total'))
                ->whereNotIn('hashtag_id', $skipIds)
                ->where('id', '>', $minId)
                ->groupBy('hashtag_id')
                ->orderBy('total', 'desc')
                ->take(config('trending.hashtags.limit'))
                ->get()
                ->map(function($h) {
                    $hashtag = Hashtag::find($h->hashtag_id);
                    if(!$hashtag) {
                        return;
                    }
                    return [
                        'id' => $h->hashtag_id,
                        'total' => $h->total,
                        'name' => '#'.$hashtag->name,
                        'hashtag' => $hashtag->name,
                        'url' => $hashtag->url()
                    ];
                })
                ->filter()
                ->values();
        });
    }

    public static function del($k)
    {
        return Cache::forget(self::key($k));
    }

    public static function refresh()
    {
        Cache::forget(self::key(':is_banned'));
        Cache::forget(self::key(':is_nsfw'));
        Cache::forget(self::key(':can_trend'));
        Cache::forget(self::key('-min-id'));
        Cache::forget(self::key());
    }
}
