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
				'app.logo'
			];

			if(!in_array($key, $allowed)) {
				return config($key);
			}

			$v = config($key);
			$c = ConfigCacheModel::where('k', $key)->first();

			if($c) {
				return $c->v;
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

		return self::get($key);
	}
}
