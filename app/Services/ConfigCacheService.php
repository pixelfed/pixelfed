<?php

namespace App\Services;

use Cache;
use Config;
use App\Models\ConfigCache as ConfigCacheModel;

class ConfigCacheService
{
	const CACHE_KEY = 'config_cache:_key:';

	public static function get($key)
	{
		$cacheKey = "config_cache:_key:{$key}";
		$ttl = now()->addHours(12);
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
				'pixelfed.oauth_enabled',
				'instance.stories.enabled',
				'pixelfed.import.instagram.enabled',
				'pixelfed.bouncer.enabled',

				'pixelfed.enforce_email_verification',
				'pixelfed.max_account_size',
				'pixelfed.enforce_account_limit',

				'uikit.custom.css',
				'uikit.custom.js',
				'uikit.show_custom.css',
				'uikit.show_custom.js'
			];

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
			Cache::forget(self::CACHE_KEY . $key);
			return self::get($key);
		}

		$cc = new ConfigCacheModel;
		$cc->k = $key;
		$cc->v = $val;
		$cc->save();

		Cache::forget(self::CACHE_KEY . $key);

		return self::get($key);
	}
}
