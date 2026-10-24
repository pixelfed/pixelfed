<?php

namespace App\Services\Account;

use App\Models\Follower;
use App\Models\Profile;
use App\Models\Status;
use App\Services\AccountService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class AccountStatService
{
    const REFRESH_CACHE_KEY = 'pf:services:accountstats:refresh:daily';

    /**
     * Status types that count toward a profile's status_count.
     *
     * Must mirror the increment/decrement logic in StatusEntityLexer and
     * StatusDelete, which only adjust status_count for media post types.
     *
     * @var array<int, string>
     */
    const COUNTABLE_STATUS_TYPES = [
        'photo',
        'photo:album',
        'video',
        'video:album',
        'photo:video:album',
    ];

    /**
     * Canonical source-of-truth status_count for a profile.
     */
    public static function recalculateStatusCount($pid): int
    {
        return (int) Status::whereProfileId($pid)
            ->whereIn('type', self::COUNTABLE_STATUS_TYPES)
            ->count();
    }

    /**
     * Canonical source-of-truth follower count for a profile.
     */
    public static function recalculateFollowerCount($pid): int
    {
        return (int) Follower::whereFollowingId($pid)->count();
    }

    /**
     * Canonical source-of-truth following count for a profile.
     */
    public static function recalculateFollowingCount($pid): int
    {
        return (int) Follower::whereProfileId($pid)->count();
    }

    /**
     * Reconcile a profile's cached count columns against source-of-truth
     * tables. Only writes/clears caches for columns that actually drifted.
     *
     * @param  array<int, string>  $only  Restrict to a subset of
     *                                    ['statuses','followers','following'].
     * @return array<string, array{cached:int,live:int,drifted:bool}>
     *                                                                Per-metric before/after summary.
     */
    public static function reconcileProfileCounts($profile, array $only = ['statuses', 'followers', 'following']): array
    {
        if (! $profile instanceof Profile) {
            $profile = Profile::find($profile);
        }

        if (! $profile) {
            return [];
        }

        $summary = [];
        $changed = false;

        if (in_array('statuses', $only, true)) {
            $cached = (int) $profile->status_count;
            $live = self::recalculateStatusCount($profile->id);
            $drift = $cached !== $live;
            if ($drift) {
                $profile->status_count = $live;
                $changed = true;
            }
            $summary['statuses'] = ['cached' => $cached, 'live' => $live, 'drifted' => $drift];
        }

        if (in_array('followers', $only, true)) {
            $cached = (int) $profile->followers_count;
            $live = self::recalculateFollowerCount($profile->id);
            $drift = $cached !== $live;
            if ($drift) {
                $profile->followers_count = $live;
                $changed = true;
            }
            $summary['followers'] = ['cached' => $cached, 'live' => $live, 'drifted' => $drift];
        }

        if (in_array('following', $only, true)) {
            $cached = (int) $profile->following_count;
            $live = self::recalculateFollowingCount($profile->id);
            $drift = $cached !== $live;
            if ($drift) {
                $profile->following_count = $live;
                $changed = true;
            }
            $summary['following'] = ['cached' => $cached, 'live' => $live, 'drifted' => $drift];
        }

        if ($changed) {
            $profile->save();

            Cache::forget('profile:status_count:'.$profile->id);
            Cache::forget('profile:follower_count:'.$profile->id);
            Cache::forget('profile:following_count:'.$profile->id);
            AccountService::del($profile->id);
        }

        return $summary;
    }

    public static function incrementPostCount($pid)
    {
        return Redis::zadd(self::REFRESH_CACHE_KEY, $pid, $pid);
    }

    public static function decrementPostCount($pid)
    {
        return Redis::zadd(self::REFRESH_CACHE_KEY, $pid, $pid);
    }

    public static function removeFromPostCount($pid)
    {
        return Redis::zrem(self::REFRESH_CACHE_KEY, $pid);
    }

    public static function getAllPostCountIncr($limit = -1)
    {
        return Redis::zrange(self::REFRESH_CACHE_KEY, 0, $limit);
    }

    public static function getPostCountChunk($lastId, $count)
    {
        return Redis::zrangebyscore(
            self::REFRESH_CACHE_KEY,
            '('.$lastId,
            '+inf',
            ['limit' => [0, $count]]
        );
    }
}
