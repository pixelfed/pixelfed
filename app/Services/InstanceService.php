<?php

namespace App\Services;

use Cache;
use App\Instance;

class InstanceService
{
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
}
