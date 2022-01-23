<?php

namespace App\Services;

use Cache;
use App\Profile;
use Illuminate\Support\Facades\Redis;
use App\Util\Webfinger\WebfingerUrl;
use Illuminate\Support\Facades\Http;
use App\Util\ActivityPub\Helpers;
use App\Transformer\Api\AccountTransformer;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

class WebfingerService
{
	public static function lookup($query)
	{
		return (new self)->run($query);
	}

	protected function run($query)
	{
		if($profile = Profile::whereUsername($query)->first()) {
			$fractal = new Fractal\Manager();
			$fractal->setSerializer(new ArraySerializer());
			$resource = new Fractal\Resource\Item($profile, new AccountTransformer());
			return $fractal->createData($resource)->toArray();
		}
		$url = WebfingerUrl::generateWebfingerUrl($query);
		if(!Helpers::validateUrl($url)) {
			return [];
		}

		$res = Http::retry(3, 500)
			->acceptJson()
			->withHeaders([
				'User-Agent' => '(Pixelfed/' . config('pixelfed.version') . '; +' . config('app.url') . ')'
			])
			->timeout(20)
			->get($url);

		if(!$res->successful()) {
			return [];
		}

		$webfinger = $res->json();
		if(!isset($webfinger['links']) || !is_array($webfinger['links']) || empty($webfinger['links'])) {
			return ['nolinks'];
		}

		$link = collect($webfinger['links'])
			->filter(function($link) {
				return $link &&
					isset($link['type']) &&
					isset($link['href']) &&
					$link['type'] == 'application/activity+json';
			})
			->pluck('href')
			->first();

		$profile = Helpers::profileFetch($link);
		$fractal = new Fractal\Manager();
		$fractal->setSerializer(new ArraySerializer());
		$resource = new Fractal\Resource\Item($profile, new AccountTransformer());
		$res = $fractal->createData($resource)->toArray();
		return $res;
	}
}
