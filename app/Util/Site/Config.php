<?php

namespace App\Util\Site;

use Cache;
use Illuminate\Support\Str;

class Config {

	const CACHE_KEY = 'api:site:configuration:_v0.7';

	public static function get() {
		return Cache::remember(self::CACHE_KEY, 900, function() {
			return [
				'open_registration' => (bool) config_cache('pixelfed.open_registration'),
				'uploader' => [
					'max_photo_size' => (int) config('pixelfed.max_photo_size'),
					'max_caption_length' => (int) config('pixelfed.max_caption_length'),
					'max_altext_length' => (int) config('pixelfed.max_altext_length', 150),
					'album_limit' => (int) config_cache('pixelfed.max_album_length'),
					'image_quality' => (int) config_cache('pixelfed.image_quality'),

					'max_collection_length' => (int) config('pixelfed.max_collection_length', 18),

					'optimize_image' => (bool) config('pixelfed.optimize_image'),
					'optimize_video' => (bool) config('pixelfed.optimize_video'),

					'media_types' => config_cache('pixelfed.media_types'),
					'mime_types' => config_cache('pixelfed.media_types') ? explode(',', config_cache('pixelfed.media_types')) : [],
					'enforce_account_limit' => (bool) config_cache('pixelfed.enforce_account_limit')
				],

				'activitypub' => [
					'enabled' => (bool) config_cache('federation.activitypub.enabled'),
					'remote_follow' => config('federation.activitypub.remoteFollow')
				],

				'ab' => config('exp'),

				'site' => [
					'name' => config_cache('app.name'),
					'domain' => config('pixelfed.domain.app'),
					'url'    => config('app.url'),
					'description' => config_cache('app.short_description')
				],

				'username' => [
					'remote' => [
						'formats' => config('instance.username.remote.formats'),
						'format' => config('instance.username.remote.format'),
						'custom' => config('instance.username.remote.custom')
					]
				],

				'features' => [
					'timelines' => [
						'local' => true,
						'network' => (bool) config('federation.network_timeline'),
					],
					'mobile_apis' => (bool) config_cache('pixelfed.oauth_enabled'),
					'stories' => (bool) config_cache('instance.stories.enabled'),
					'video'	=> Str::contains(config_cache('pixelfed.media_types'), 'video/mp4'),
					'import' => [
						'instagram' => (bool) config_cache('pixelfed.import.instagram.enabled'),
						'mastodon' => false,
						'pixelfed' => false
					],
					'label' => [
						'covid' => [
							'enabled' => (bool) config('instance.label.covid.enabled'),
							'org' => config('instance.label.covid.org'),
							'url' => config('instance.label.covid.url'),
						]
					]
				]
			];
		});
	}

	public static function json() {
		return json_encode(self::get(), JSON_FORCE_OBJECT);
	}
}
