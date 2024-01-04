<?php

namespace App\Services;

use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Status;
use App\User;
use App\Services\AccountService;
use App\Util\Site\Nodeinfo;

class LandingService
{
	public static function get($json = true)
	{
		$activeMonth = Nodeinfo::activeUsersMonthly();

		$totalUsers = Cache::remember('api:nodeinfo:users', 43200, function() {
			return User::count();
		});

		$postCount = Cache::remember('api:nodeinfo:statuses', 21600, function() {
			return Status::whereLocal(true)->count();
		});

		$contactAccount = Cache::remember('api:v1:instance-data:contact', 604800, function () {
			if(config_cache('instance.admin.pid')) {
				return AccountService::getMastodon(config_cache('instance.admin.pid'), true);
			}
			$admin = User::whereIsAdmin(true)->first();
			return $admin && isset($admin->profile_id) ?
				AccountService::getMastodon($admin->profile_id, true) :
				null;
		});

		$rules = Cache::remember('api:v1:instance-data:rules', 604800, function () {
			return config_cache('app.rules') ?
				collect(json_decode(config_cache('app.rules'), true))
				->map(function($rule, $key) {
					$id = $key + 1;
					return [
						'id' => "{$id}",
						'text' => $rule
					];
				})
				->toArray() : [];
		});

		$res = [
			'name' => config_cache('app.name'),
			'url' => config_cache('app.url'),
			'domain' => config('pixelfed.domain.app'),
			'show_directory' => config_cache('instance.landing.show_directory'),
			'show_explore_feed' => config_cache('instance.landing.show_explore'),
			'open_registration' => config_cache('pixelfed.open_registration') == 1,
			'version' => config('pixelfed.version'),
			'about' => [
				'banner_image' => config_cache('app.banner_image') ?? url('/storage/headers/default.jpg'),
				'short_description' => config_cache('app.short_description'),
				'description' => config_cache('app.description'),
			],
			'stats' => [
				'active_users' => (int) $activeMonth,
				'posts_count' => (int) $postCount,
				'total_users' => (int) $totalUsers
			],
			'contact' => [
				'account' => $contactAccount,
				'email' => config('instance.email')
			],
			'rules' => $rules,
			'uploader' => [
				'max_photo_size' => (int) (config('pixelfed.max_photo_size') * 1024),
				'max_caption_length' => (int) config('pixelfed.max_caption_length'),
				'max_altext_length' => (int) config('pixelfed.max_altext_length', 150),
				'album_limit' => (int) config_cache('pixelfed.max_album_length'),
				'image_quality' => (int) config_cache('pixelfed.image_quality'),
				'max_collection_length' => (int) config('pixelfed.max_collection_length', 18),
				'optimize_image' => (bool) config('pixelfed.optimize_image'),
				'optimize_video' => (bool) config('pixelfed.optimize_video'),
				'media_types' => config_cache('pixelfed.media_types'),
			],
			'features' => [
				'federation' => config_cache('federation.activitypub.enabled'),
				'timelines' => [
					'local' => true,
					'network' => (bool) config('federation.network_timeline'),
				],
				'mobile_apis' => (bool) config_cache('pixelfed.oauth_enabled'),
				'stories' => (bool) config_cache('instance.stories.enabled'),
				'video'	=> Str::contains(config_cache('pixelfed.media_types'), 'video/mp4'),
			]
		];

		if($json) {
			return json_encode($res);
		}

		return $res;
	}
}
