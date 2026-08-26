<?php

namespace App\Services;

use App\Models\UserDomainBlock;
use App\UserFilter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class UserFilterService
{
    const USER_MUTES_KEY = 'pf:services:mutes:ids:';

    const USER_BLOCKS_KEY = 'pf:services:blocks:ids:';

    const USER_DOMAIN_KEY = 'pf:services:domain-blocks:ids:';

    const EMPTY_SENTINEL = '-1';

    const FILTER_TTL = 2592000;

    public static function mutes(int $profile_id)
    {
        return self::getFilterIds($profile_id, 'mute', self::USER_MUTES_KEY);
    }

    public static function blocks(int $profile_id)
    {
        return self::getFilterIds($profile_id, 'block', self::USER_BLOCKS_KEY);
    }

    protected static function getFilterIds(int $profile_id, string $type, string $keyPrefix)
    {
        $key = $keyPrefix.$profile_id;

        $ids = Redis::zrevrange($key, 0, -1);
        if (! empty($ids)) {
            Redis::expire($key, self::FILTER_TTL);

            return array_values(array_filter($ids, fn ($id) => $id !== self::EMPTY_SENTINEL));
        }

        Cache::forget($key.':cached-v0');

        $ids = UserFilter::whereFilterType($type)
            ->whereUserId($profile_id)
            ->pluck('filterable_id')
            ->map(fn ($id) => AccountService::get($id, true)['id'] ?? false)
            ->filter()
            ->values()
            ->toArray();

        if (empty($ids)) {
            Redis::zadd($key, 0, self::EMPTY_SENTINEL);
        } else {
            foreach ($ids as $id) {
                Redis::zadd($key, (int) $id, (int) $id);
            }
        }
        Redis::expire($key, self::FILTER_TTL);

        return $ids;
    }

    protected static function addToFilter(string $key, int $filterable_id)
    {
        Redis::zadd($key, $filterable_id, $filterable_id);
        Redis::zrem($key, self::EMPTY_SENTINEL);
        Redis::expire($key, self::FILTER_TTL);
    }

    protected static function removeFromFilter(string $key, $filterable_id)
    {
        Redis::zrem($key, $filterable_id);
        if (Redis::zcard($key) === 0) {
            Redis::zadd($key, 0, self::EMPTY_SENTINEL);
        }
        Redis::expire($key, self::FILTER_TTL);
    }

    public static function filters(int $profile_id)
    {
        return array_unique(array_merge(self::mutes($profile_id), self::blocks($profile_id)));
    }

    public static function mute(int $profile_id, int $muted_id)
    {
        if ($profile_id == $muted_id) {
            return false;
        }
        $key = self::USER_MUTES_KEY.$profile_id;
        $exists = in_array($muted_id, self::mutes($profile_id));
        if (! $exists) {
            self::addToFilter($key, $muted_id);
        }

        return true;
    }

    public static function unmute(int $profile_id, string $muted_id)
    {
        if ($profile_id == $muted_id) {
            return false;
        }
        $key = self::USER_MUTES_KEY.$profile_id;
        $exists = in_array($muted_id, self::mutes($profile_id));
        if ($exists) {
            self::removeFromFilter($key, $muted_id);
        }

        return true;
    }

    public static function block(int $profile_id, int $blocked_id)
    {
        if ($profile_id == $blocked_id) {
            return false;
        }
        $key = self::USER_BLOCKS_KEY.$profile_id;
        $exists = in_array($blocked_id, self::blocks($profile_id));
        if (! $exists) {
            self::addToFilter($key, $blocked_id);
        }

        return true;
    }

    public static function unblock(int $profile_id, string $blocked_id)
    {
        if ($profile_id == $blocked_id) {
            return false;
        }
        $key = self::USER_BLOCKS_KEY.$profile_id;
        $exists = in_array($blocked_id, self::blocks($profile_id));
        if ($exists) {
            self::removeFromFilter($key, $blocked_id);
        }

        return $exists;
    }

    public static function blockCount(int $profile_id)
    {
        return count(self::blocks($profile_id));
    }

    public static function muteCount(int $profile_id)
    {
        return count(self::mutes($profile_id));
    }

    public static function domainBlocks($pid, $purge = false)
    {
        if ($purge) {
            Cache::forget(self::USER_DOMAIN_KEY.$pid);
        }

        return Cache::remember(
            self::USER_DOMAIN_KEY.$pid,
            21600,
            function () use ($pid) {
                return UserDomainBlock::whereProfileId($pid)->pluck('domain')->toArray();
            }
        );
    }
}
