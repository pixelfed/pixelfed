<?php

namespace App\Services;

use App\Models\ImportPost;
use Cache;

class ImportService
{
    const CACHE_KEY = 'pf:import-service:';

    public static function getId($userId, $year, $month, $day)
    {
        if($userId > 999999) {
            return;
        }
        if($year < 9 || $year > 23) {
            return;
        }
        if($month < 1 || $month > 12) {
            return;
        }
        if($day < 1 || $day > 31) {
            return;
        }
        $start = 1;
        $key = self::CACHE_KEY . 'getIdRange:incr:byUserId:' . $userId . ':y-' . $year . ':m-' . $month . ':d-' . $day;
        $incr = Cache::increment($key, random_int(5, 19));
        if($incr > 999) {
            $daysInMonth = now()->parse($day . '-' . $month . '-' . $year)->daysInMonth;

            if($month == 12) {
                $year = $year + 1;
                $month = 1;
                $day = 0;
            }

            if($day + 1 >= $daysInMonth) {
                $day = 1;
                $month = $month + 1;
            } else {
                $day = $day + 1;
            }
            return self::getId($userId, $year, $month, $day);
        }
        $uid = str_pad($userId, 6, 0, STR_PAD_LEFT);
        $year = str_pad($year, 2, 0, STR_PAD_LEFT);
        $month = str_pad($month, 2, 0, STR_PAD_LEFT);
        $day = str_pad($day, 2, 0, STR_PAD_LEFT);
        $zone = $year . $month . $day . str_pad($incr, 3, 0, STR_PAD_LEFT);
        return [
            'id' => $start . $uid . $zone,
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'incr' => $incr,
        ];
    }

    public static function getPostCount($profileId, $refresh = false)
    {
        $key = self::CACHE_KEY . 'totalPostCountByProfileId:' . $profileId;
        if($refresh) {
            Cache::forget($key);
        }
        return intval(Cache::remember($key, 21600, function() use($profileId) {
            return ImportPost::whereProfileId($profileId)->whereSkipMissingMedia(false)->count();
        }));
    }

    public static function getAttempts($profileId)
    {
        $key = self::CACHE_KEY . 'attemptsByProfileId:' . $profileId;
        return intval(Cache::remember($key, 21600, function() use($profileId) {
            return ImportPost::whereProfileId($profileId)
                ->whereSkipMissingMedia(false)
                ->get()
                ->groupBy(function($item) {
                    return $item->created_at->format('Y-m-d');
                })
                ->count();
        }));
    }

    public static function clearAttempts($profileId)
    {
        $key = self::CACHE_KEY . 'attemptsByProfileId:' . $profileId;
        return Cache::forget($key);
    }

    public static function getImportedFiles($profileId, $refresh = false)
    {
        $key = self::CACHE_KEY . 'importedPostsByProfileId:' . $profileId;
        if($refresh) {
            Cache::forget($key);
        }
        return Cache::remember($key, 21600, function() use($profileId) {
            return ImportPost::whereProfileId($profileId)
                ->get()
                ->filter(function($ip) {
                    return StatusService::get($ip->status_id);
                })
                ->map(function($ip) {
                    return collect($ip->media)->map(function($m) { return $m['uri']; });
                })->values()->flatten();
        });
    }

    public static function clearImportedFiles($profileId)
    {
        $key = self::CACHE_KEY . 'importedPostsByProfileId:' . $profileId;
        return Cache::forget($key);
    }
}
