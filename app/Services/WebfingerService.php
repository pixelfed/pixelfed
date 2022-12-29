<?php

namespace App\Services;

use Cache;
use App\Profile;
use App\Util\Webfinger\WebfingerUrl;
use Illuminate\Support\Facades\Http;
use App\Util\ActivityPub\Helpers;
use App\Services\AccountService;

class WebfingerService
{
	public static function lookup($query, $mastodonMode = false)
	{
		return (new self)->run($query, $mastodonMode);
	}

	protected function run($query, $mastodonMode)
	{
		if($profile = Profile::whereUsername($query)->first()) {
			return $mastodonMode ?
				AccountService::getMastodon($profile->id, true) :
				AccountService::get($profile->id);
		}
		$url = WebfingerUrl::generateWebfingerUrl($query);
		if(!Helpers::validateUrl($url)) {
			return [];
		}

		try {
			$res = Http::retry(3, 100)
				->acceptJson()
				->withHeaders([
					'User-Agent' => '(Pixelfed/' . config('pixelfed.version') . '; +' . config('app.url') . ')'
				])
				->timeout(20)
				->get($url);
		} catch (\Illuminate\Http\Client\ConnectionException $e) {
			return [];
		}

		if(!$res->successful()) {
			return [];
		}

		$webfinger = $res->json();
		if(!isset($webfinger['links']) || !is_array($webfinger['links']) || empty($webfinger['links'])) {
			return [];
		}

		$link = collect($webfinger['links'])
			->filter(function($link) {
				return $link &&
					isset($link['rel'], $link['type'], $link['href']) &&
					$link['rel'] === 'self' &&
					in_array($link['type'], ['application/activity+json','application/ld+json; profile="https://www.w3.org/ns/activitystreams"']);
			})
			->pluck('href')
			->first();

		$profile = Helpers::profileFetch($link);
		if(!$profile) {
			return;
		}
		return $mastodonMode ?
			AccountService::getMastodon($profile->id, true) :
			AccountService::get($profile->id);
	}
}
