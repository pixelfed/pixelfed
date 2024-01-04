<?php

namespace App\Services;

use App\Models\AdminShadowFilter;
use Cache;

class AdminShadowFilterService
{
    const CACHE_KEY = 'pf:services:asfs:';

    public static function queryFilter($name = 'hide_from_public_feeds')
    {
        return AdminShadowFilter::whereItemType('App\Profile')
            ->whereActive(1)
            ->where('hide_from_public_feeds', true)
            ->pluck('item_id')
            ->toArray();
    }

    public static function getHideFromPublicFeedsList($refresh = false)
    {
        $key = self::CACHE_KEY . 'list:hide_from_public_feeds';
        if($refresh) {
            Cache::forget($key);
        }
        return Cache::remember($key, 86400, function() {
            return AdminShadowFilter::whereItemType('App\Profile')
                ->whereActive(1)
                ->where('hide_from_public_feeds', true)
                ->pluck('item_id')
                ->toArray();
        });
    }

    public static function canAddToPublicFeedByProfileId($profileId)
    {
        return !in_array($profileId, self::getHideFromPublicFeedsList());
    }

    public static function refresh()
    {
        $keys = [
            self::CACHE_KEY . 'list:hide_from_public_feeds'
        ];

        foreach($keys as $key) {
            Cache::forget($key);
        }
    }
}
