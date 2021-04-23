<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Status;
//use App\Transformer\Api\v3\StatusTransformer;
use App\Transformer\Api\StatusStatelessTransformer;
use App\Transformer\Api\StatusTransformer;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

class StatusService {

	const CACHE_KEY = 'pf:services:status:';

	public static function key($id)
	{
		return self::CACHE_KEY . $id;
	}

	public static function get($id)
	{
		return Cache::remember(self::key($id), now()->addDays(7), function() use($id) {
			$status = Status::whereScope('public')->find($id);
			if(!$status) {
				return null;
			}
			$fractal = new Fractal\Manager();
			$fractal->setSerializer(new ArraySerializer());
			$resource = new Fractal\Resource\Item($status, new StatusStatelessTransformer());
			return $fractal->createData($resource)->toArray();
		});
	}

	public static function del($id)
	{
		return Cache::forget(self::key($id));
	}
}
