<?php

namespace App\Util\Site;

use App\Models\User;
use App\Services\InstanceService;
use Illuminate\Support\Facades\Cache;

class Nodeinfo
{
    const CACHE_KEY = 'api:nodeinfo';

    const CACHE_KEY_ACTIVE_MONTH = 'api:nodeinfo:active-users-monthly:v2';

    const CACHE_KEY_ACTIVE_HALF_YEAR = 'api:nodeinfo:active-users-half-year:v2';

    const CACHE_TTL_ACTIVE_USERS = 3600;

    const LEGACY_CACHE_KEYS = [
        'api:nodeinfo:active-users-monthly',
        'api:nodeinfo:active-users-half-year',
    ];

    const DEPENDENT_CACHE_KEYS = [
        self::CACHE_KEY,
        'api:v2:instance-data-response-v2',
    ];

    public static function get()
    {
        $res = Cache::remember(self::CACHE_KEY, 900, function () {
            $activeHalfYear = self::activeUsersHalfYear();
            $activeMonth = self::activeUsersMonthly();

            $users = Cache::remember('api:nodeinfo:users', 43200, function () {
                return User::count();
            });

            $statuses = InstanceService::totalLocalStatuses();

            $features = ['features' => Config::get()['features']];
            unset($features['features']['hls']);

            return [
                'metadata' => [
                    'nodeName' => config_cache('app.name'),
                    'software' => [
                        'homepage' => 'https://pixelfed.org',
                        'repo' => 'https://github.com/pixelfed/pixelfed',
                    ],
                    'config' => $features,
                ],
                'protocols' => [
                    'activitypub',
                ],
                'services' => [
                    'inbound' => [],
                    'outbound' => [],
                ],
                'software' => [
                    'name' => 'pixelfed',
                    'version' => config('pixelfed.version'),
                ],
                'usage' => [
                    'localPosts' => (int) $statuses,
                    'localComments' => 0,
                    'users' => [
                        'total' => (int) $users,
                        'activeHalfyear' => (int) $activeHalfYear,
                        'activeMonth' => (int) $activeMonth,
                    ],
                ],
                'version' => '2.0',
            ];
        });
        $res['openRegistrations'] = (bool) config_cache('pixelfed.open_registration');

        return $res;
    }

    public static function wellKnown(): array
    {
        return [
            'links' => [
                [
                    'href' => config('pixelfed.nodeinfo.url'),
                    'rel' => 'http://nodeinfo.diaspora.software/ns/schema/2.0',
                ],
            ],
        ];
    }

    public static function activeUsersMonthly()
    {
        return Cache::remember(self::CACHE_KEY_ACTIVE_MONTH, self::CACHE_TTL_ACTIVE_USERS, function () {
            return self::countActiveMonth();
        });
    }

    public static function activeUsersHalfYear()
    {
        return Cache::remember(self::CACHE_KEY_ACTIVE_HALF_YEAR, self::CACHE_TTL_ACTIVE_USERS, function () {
            return self::countActiveHalfYear();
        });
    }

    public static function refreshActiveUsers(): array
    {
        $activeMonth = self::countActiveMonth();
        $activeHalfYear = self::countActiveHalfYear();

        Cache::put(self::CACHE_KEY_ACTIVE_MONTH, $activeMonth, self::CACHE_TTL_ACTIVE_USERS);
        Cache::put(self::CACHE_KEY_ACTIVE_HALF_YEAR, $activeHalfYear, self::CACHE_TTL_ACTIVE_USERS);

        foreach (array_merge(self::LEGACY_CACHE_KEYS, self::DEPENDENT_CACHE_KEYS) as $key) {
            Cache::forget($key);
        }

        return [
            'activeMonth' => $activeMonth,
            'activeHalfyear' => $activeHalfYear,
        ];
    }

    protected static function countActiveMonth(): int
    {
        return self::activeUsersSince(now()->subDays(30));
    }

    protected static function countActiveHalfYear(): int
    {
        return self::activeUsersSince(now()->subMonths(6));
    }

    protected static function activeUsersSince($since): int
    {
        return User::query()
            ->whereNotNull('last_active_at')
            ->where('last_active_at', '>', $since)
            ->count();
    }
}
