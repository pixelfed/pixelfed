<?php

namespace App\Services;

use Cache;
use App\Profile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Util\Webfinger\WebfingerUrl;

class AutolinkService
{
	const CACHE_KEY = 'pf:services:autolink:';

	public static function mentionedUsernameExists($username)
	{
		$key = 'pf:services:autolink:userexists:' . hash('sha256', $username);

		return Cache::remember($key, 3600, function() use($username) {
			$remote = Str::of($username)->contains('@');
			$profile = Profile::whereUsername($username)->first();
			if($profile) {
				if($profile->domain != null) {
					$instance = InstanceService::getByDomain($profile->domain);
					if($instance && $instance->banned == true) {
						return false;
					}
				}
				return true;
			} else {
				if($remote) {
					$parts = explode('@', $username);
					$domain = last($parts);
					$instance = InstanceService::getByDomain($domain);

					if($instance) {
						if($instance->banned == true) {
							return false;
						} else {
							$wf = WebfingerUrl::generateWebfingerUrl($username);
							$res = Http::head($wf);
							return $res->ok();
						}
					} else {
						$wf = WebfingerUrl::generateWebfingerUrl($username);
						$res = Http::head($wf);
						return $res->ok();
					}
				}
			}
			return false;
		});
	}
}
