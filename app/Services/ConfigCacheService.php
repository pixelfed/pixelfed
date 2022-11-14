<?php

namespace App\Services;

use Cache;
use Config;
use App\Models\ConfigCache as ConfigCacheModel;

class ConfigCacheService
{
	const CACHE_KEY = 'config_cache:_v0-key:';

	public static function get($key)
	{
		$cacheKey = self::CACHE_KEY . $key;
		$ttl = now()->addHours(12);
		if(!config('instance.enable_cc')) {
			return config($key);
		}

		return Cache::remember($cacheKey, $ttl, function() use($key) {

			$allowed = [
				'app.name',
				'app.short_description',
				'app.description',
				'app.rules',

				'pixelfed.max_photo_size',
				'pixelfed.max_album_length',
				'pixelfed.image_quality',
				'pixelfed.media_types',

				'pixelfed.open_registration',
				'federation.activitypub.enabled',
				'instance.stories.enabled',
				'pixelfed.oauth_enabled',
				'pixelfed.import.instagram.enabled',
				'pixelfed.bouncer.enabled',

				'pixelfed.enforce_email_verification',
				'pixelfed.max_account_size',
				'pixelfed.enforce_account_limit',

				'uikit.custom.css',
				'uikit.custom.js',
				'uikit.show_custom.css',
				'uikit.show_custom.js',
				'about.title',

				'pixelfed.cloud_storage',

				'account.autofollow',
				'account.autofollow_usernames',
				'config.discover.features',

				'instance.has_legal_notice',

				'pixelfed.directory',
				'app.banner_image',
				'pixelfed.directory.submission-key',
				'pixelfed.directory.submission-ts',
				'pixelfed.directory.has_submitted',
				'pixelfed.directory.latest_response',
				'pixelfed.directory.is_synced',
				'pixelfed.directory.testimonials',
				// 'system.user_mode'
			];

			if(!config('instance.enable_cc')) {
				return config($key);
			}

			if(!in_array($key, $allowed)) {
				return config($key);
			}

			$v = config($key);
			$c = ConfigCacheModel::where('k', $key)->first();

			if($c) {
				return $c->v ?? config($key);
			}

			if(!$v) {
				return;
			}

			$cc = new ConfigCacheModel;
			$cc->k = $key;
			$cc->v = $v;
			$cc->save();

			return $v;
		});
	}

	public static function put($key, $val)
	{
		$exists = ConfigCacheModel::whereK($key)->first();

		if($exists) {
			$exists->v = $val;
			$exists->save();
			Cache::put(self::CACHE_KEY . $key, $val, now()->addHours(12));
			return self::get($key);
		}

		$cc = new ConfigCacheModel;
		$cc->k = $key;
		$cc->v = $val;
		$cc->save();

		Cache::put(self::CACHE_KEY . $key, $val, now()->addHours(12));

		return self::get($key);
	}
}
