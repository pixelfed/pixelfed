<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Services;

use Cache;
use App\Profile;
use Illuminate\Support\Facades\Redis;
use App\Util\Webfinger\WebfingerUrl;
use Zttp\Zttp;
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
		$res = Zttp::get($url);
		$webfinger = $res->json();
		if(!isset($webfinger['links'])) {
			return [];
		}
		$profile = Helpers::profileFetch($webfinger['links'][0]['href']);
		$fractal = new Fractal\Manager();
		$fractal->setSerializer(new ArraySerializer());
		$resource = new Fractal\Resource\Item($profile, new AccountTransformer());
		$res = $fractal->createData($resource)->toArray();
		return $res;
	}
}
