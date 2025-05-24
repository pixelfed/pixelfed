<?php

namespace App\Services;

use App\Status;
use Cache;

class PlaceService
{
    const STATUSES_CACHE_KEY = 'pf:places:v0:sid-cache:by:placeid:';

    public static function clearStatusesByPlaceId($placeId = false)
    {
        if (! $placeId) {
            return;
        }

        return Cache::forget(self::STATUSES_CACHE_KEY.$placeId);
    }

    public static function getStatusesByPlaceId($placeId = false)
    {
        if (! $placeId) {
            return [];
        }

        return Cache::remember(self::STATUSES_CACHE_KEY.$placeId, now()->addDays(4), function () use ($placeId) {
            return Status::select('id')
                ->wherePlaceId($placeId)
                ->whereScope('public')
                ->whereIn('type', ['photo', 'photo:album', 'video'])
                ->orderByDesc('id')
                ->limit(150)
                ->get();
        });
    }
}
