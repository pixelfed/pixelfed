<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DiscoverService
{
    public static function getDailyIdPool()
    {
        $min_id = SnowflakeService::byDate(now()->subMonths(3));

        // One post per author (the newest, MAX(id)) so the pool is not
        // dominated by prolific accounts. Selecting the aggregated MAX(id) with
        // GROUP BY profile_id is valid under ONLY_FULL_GROUP_BY and portable
        // across every driver — the previous `select id ... group by profile_id`
        // threw 1055 on strict MySQL/MariaDB, and was gated off entirely on
        // other drivers (no dedup at all). inRandomOrder() still shuffles the
        // pool. This mirrors the MAX(id)+GROUP BY dedup used elsewhere.
        return DB::table('statuses')
            ->selectRaw('MAX(id) as id')
            ->whereNull('uri')
            ->whereType('photo')
            ->whereIsNsfw(false)
            ->whereVisibility('public')
            ->where('id', '>', $min_id)
            ->groupBy('profile_id')
            ->inRandomOrder()
            ->take(300)
            ->pluck('id');
    }

    public static function getForYou()
    {
        return Cache::remember('pf:services:discover:for-you', 21600, function () {
            return self::getDailyIdPool();
        });
    }
}
