<?php

namespace App\Util\Site;

use Cache;
use App\{Like, Profile, Status, User};
use Illuminate\Support\Str;

class Nodeinfo {

	public static function get()
	{
		$res = Cache::remember('api:nodeinfo', 300, function () {
			$activeHalfYear = Cache::remember('api:nodeinfo:ahy', 172800, function() {
				return User::select('last_active_at')
					->where('last_active_at', '>', now()->subMonths(6))
					->orWhere('created_at', '>', now()->subMonths(6))
					->count();
			});

			$activeMonth = Cache::remember('api:nodeinfo:am', 172800, function() {
				return User::select('last_active_at')
					->where('last_active_at', '>', now()->subMonths(1))
					->orWhere('created_at', '>', now()->subMonths(1))
					->count();
			});

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
					'localPosts'    => $statuses,
					'localComments' => 0,
					'users'         => [
						'total'          => $users,
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

}
