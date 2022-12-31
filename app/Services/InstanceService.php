<?php

namespace App\Services;

use Cache;
use App\Instance;

class InstanceService
{
	const CACHE_KEY_BANNED_DOMAINS = 'instances:banned:domains';
	const CACHE_KEY_UNLISTED_DOMAINS = 'instances:unlisted:domains';
	const CACHE_KEY_NSFW_DOMAINS = 'instances:auto_cw:domains';

	public static function getByDomain($domain)
	{
		return Cache::remember('pf:services:instance:by_domain:'.$domain, 3600, function() use($domain) {
			return Instance::whereDomain($domain)->first();
		});
	}

	public static function getBannedDomains()
	{
		return Cache::remember(self::CACHE_KEY_BANNED_DOMAINS, 1209600, function() {
			return Instance::whereBanned(true)->pluck('domain')->toArray();
		});
	}

	public static function getUnlistedDomains()
	{
		return Cache::remember(self::CACHE_KEY_UNLISTED_DOMAINS, 1209600, function() {
			return Instance::whereUnlisted(true)->pluck('domain')->toArray();
		});
	}

	public static function getNsfwDomains()
	{
		return Cache::remember(self::CACHE_KEY_NSFW_DOMAINS, 1209600, function() {
			return Instance::whereAutoCw(true)->pluck('domain')->toArray();
		});
	}

	public static function software($domain)
	{
		$key = 'instances:software:' . strtolower($domain);
		return Cache::remember($key, 86400, function() use($domain) {
			$instance = Instance::whereDomain($domain)->first();
			if(!$instance) {
				return;
			}
			return $instance->software;
		});
	}
}
