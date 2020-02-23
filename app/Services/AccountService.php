<?php

namespace App\Services;

use Cache;
use App\Profile;
use App\Transformer\Api\AccountTransformer;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class AccountService {

	const CACHE_KEY = 'pf:services:account:';

	public static function get($id): array
	{
		// $key = self::CACHE_KEY . ':' . $id;
		// $ttl = now()->addSeconds(10);
		// return Cache::remember($key, $ttl, function() use($id) {
		// });
		
		$fractal = new Fractal\Manager();
		$fractal->setSerializer(new ArraySerializer());
		$profile = Profile::whereNull('status')->findOrFail($id);
		$resource = new Fractal\Resource\Item($profile, new AccountTransformer());
		return $fractal->createData($resource)->toArray();
	}

}