<?php

namespace App\Services;

use Cache;
use App\Instance;

class InstanceService
{
	public static function getByDomain($domain)
	{
		return Cache::remember('pf:services:instance:by_domain:'.$domain, 3600, function() use($domain) {
			return Instance::whereDomain($domain)->first();
		});
	}

	public static function getBannedDomains()
	{
		return Cache::remember('instances:banned:domains', now()->addHours(12), function() {
			return Instance::whereBanned(true)->pluck('domain')->toArray();
		});
	}

	public static function getUnlistedDomains()
	{
		return Cache::remember('instances:unlisted:domains', now()->addHours(12), function() {
			return Instance::whereUnlisted(true)->pluck('domain')->toArray();
		});
	}

	public static function getNsfwDomains()
	{
		return Cache::remember('instances:auto_cw:domains', now()->addHours(12), function() {
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
