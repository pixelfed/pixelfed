<?php

namespace App\Services;

use Cache;
use Illuminate\Support\Facades\Redis;
use App\Transformer\Api\AccountTransformer;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use App\Profile;

class ProfileService {

	public static function get($id)
	{
		$key = 'profile:model:' . $id;
		$ttl = now()->addHours(4);
		$res = Cache::remember($key, $ttl, function() use($id) {
			$profile = Profile::find($id);
			if(!$profile) {
				return false;
			}
			$fractal = new Fractal\Manager();
			$fractal->setSerializer(new ArraySerializer());
			$resource = new Fractal\Resource\Item($profile, new AccountTransformer());
			return $fractal->createData($resource)->toArray();
		});
		return $res;
	}

}
