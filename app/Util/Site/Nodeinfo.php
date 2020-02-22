<?php

namespace App\Util\Site;

use Cache;
use App\{Like, Profile, Status, User};
use Illuminate\Support\Str;

class Nodeinfo {

	public static function get()
	{
        $res = Cache::remember('api:nodeinfo', now()->addMinutes(15), function () {
            $activeHalfYear = Cache::remember('api:nodeinfo:ahy', now()->addHours(12), function() {
                $count = collect([]);
                $likes = Like::select('profile_id')->with('actor')->where('created_at', '>', now()->subMonths(6)->toDateTimeString())->groupBy('profile_id')->get()->filter(function($like) {return $like->actor && $like->actor->domain == null;})->pluck('profile_id')->toArray();
                $count = $count->merge($likes);
                $statuses = Status::select('profile_id')->whereLocal(true)->where('created_at', '>', now()->subMonths(6)->toDateTimeString())->groupBy('profile_id')->pluck('profile_id')->toArray();
                $count = $count->merge($statuses);
                $profiles = Profile::select('id')->whereNull('domain')->where('created_at', '>', now()->subMonths(6)->toDateTimeString())->groupBy('id')->pluck('id')->toArray();
                $count = $count->merge($profiles);
                return $count->unique()->count();
            });
            $activeMonth = Cache::remember('api:nodeinfo:am', now()->addHours(12), function() {
                $count = collect([]);
                $likes = Like::select('profile_id')->where('created_at', '>', now()->subMonths(1)->toDateTimeString())->groupBy('profile_id')->get()->filter(function($like) {return $like->actor && $like->actor->domain == null;})->pluck('profile_id')->toArray();
                $count = $count->merge($likes);
                $statuses = Status::select('profile_id')->whereLocal(true)->where('created_at', '>', now()->subMonths(1)->toDateTimeString())->groupBy('profile_id')->pluck('profile_id')->toArray();
                $count = $count->merge($statuses);
                $profiles = Profile::select('id')->whereNull('domain')->where('created_at', '>', now()->subMonths(1)->toDateTimeString())->groupBy('id')->pluck('id')->toArray();
                $count = $count->merge($profiles);
                return $count->unique()->count();
            });
            return [
                'metadata' => [
                    'nodeName' => config('pixelfed.domain.app'),
                    'software' => [
                        'homepage'  => 'https://pixelfed.org',
                        'repo'      => 'https://github.com/pixelfed/pixelfed',
                    ],
                    'config' => \App\Util\Site\Config::get()
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
                    'localPosts'    => Status::whereLocal(true)->count(),
                    'localComments' => 0,
                    'users'         => [
                        'total'          => User::count(),
                        'activeHalfyear' => (int) $activeHalfYear,
                        'activeMonth'    => (int) $activeMonth,
                    ],
                ],
                'version' => '2.0',
            ];
        });
        $res['openRegistrations'] = config('pixelfed.open_registration');
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

}