<?php

namespace App\Util\Site;

use Illuminate\Support\Facades\Cache;
use App\Like;
use App\Profile;
use App\Status;
use App\User;
use Illuminate\Support\Str;

class Nodeinfo
{
    public static function get()
    {
        $res = Cache::remember('api:nodeinfo', 900, function () {
            $activeHalfYear = self::activeUsersHalfYear();
            $activeMonth = self::activeUsersMonthly();

            $users = Cache::remember('api:nodeinfo:users', 43200, function() {
                return User::count();
            });

            $statuses = Cache::remember('api:nodeinfo:statuses', 21600, function() {
                return Status::whereLocal(true)->count();
            });

            $features = [ 'features' => \App\Util\Site\Config::get()['features'] ];

            return [
                'metadata' => [
                    'nodeName' => config_cache('app.name'),
                    'software' => [
                        'homepage'  => 'https://pixelfed.org',
                        'repo'      => 'https://github.com/pixelfed/pixelfed',
                    ],
                    'config' => $features
                ],
                'protocols'         => [
                    'activitypub',
                ],
                'services' => [
                    'inbound'  => [],
                    'outbound' => [],
                ],
                'software' => [
                    'name'          => 'pixelfed',
                    'version'       => config('pixelfed.version'),
                ],
                'usage' => [
                    'localPosts'    => (int) $statuses,
                    'localComments' => 0,
                    'users'         => [
                        'total'          => (int) $users,
                        'activeHalfyear' => (int) $activeHalfYear,
                        'activeMonth'    => (int) $activeMonth,
                    ],
                ],
                'version' => '2.0',
            ];
        });
        $res['openRegistrations'] = (bool) config_cache('pixelfed.open_registration');
        return $res;
    }

    public static function wellKnown()
    {
        return [
            'links' => [
                [
                    'href' => config('pixelfed.nodeinfo.url'),
                    'rel'  => 'http://nodeinfo.diaspora.software/ns/schema/2.0',
                ],
            ],
        ];
    }

    public static function activeUsersMonthly()
    {
        return Cache::remember('api:nodeinfo:active-users-monthly', 43200, function() {
            return User::withTrashed()
                    ->select('last_active_at, updated_at')
                    ->where('updated_at', '>', now()->subWeeks(5))
                    ->orWhere('last_active_at', '>', now()->subWeeks(5))
                    ->count();
        });
    }

    public static function activeUsersHalfYear()
    {
        return Cache::remember('api:nodeinfo:active-users-half-year', 43200, function() {
            return User::withTrashed()
                ->select('last_active_at, updated_at')
                ->where('last_active_at', '>', now()->subMonths(6))
                ->orWhere('updated_at', '>', now()->subMonths(6))
                ->count();
        });
    }
}
