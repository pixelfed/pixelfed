<?php

use App\Services\ConfigCacheService;

if (!function_exists('config_cache')) {
	function config_cache($key) {
		return ConfigCacheService::get($key);
	}
}
