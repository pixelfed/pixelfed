<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Services;

use Cache;
use Illuminate\Support\Facades\Redis;
use App\{
	Notification,
	Profile
};
use App\Transformer\Api\NotificationTransformer;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

class NotificationService {

	const CACHE_KEY = 'pf:services:notifications:ids:';

	public static function get($id, $start = 0, $stop = 400)
	{
		$res = collect([]);
		$key = self::CACHE_KEY . $id;
		$stop = $stop > 400 ? 400 : $stop;
		$ids = Redis::zrangebyscore($key, $start, $stop);
		if(empty($ids)) {
			$ids = self::coldGet($id, $start, $stop);
		}
		foreach($ids as $id) {
			$res->push(self::getNotification($id));
		}
		return $res;
	}

	public static function coldGet($id, $start = 0, $stop = 400)
	{
		$stop = $stop > 400 ? 400 : $stop;
		$ids = Notification::whereProfileId($id)
			->latest()
			->skip($start)
			->take($stop)
			->pluck('id');
		foreach($ids as $key) {
			self::set($id, $key);
		}
		return $ids;
	}

	public static function set($id, $val)
	{
		return Redis::zadd(self::CACHE_KEY . $id, $val, $val);
	}

	public static function del($id, $val)
	{
		return Redis::zrem(self::CACHE_KEY . $id, $val);
	}

	public static function add($id, $val)
	{
		return self::set($id, $val);
	}

	public static function rem($id, $val)
	{
		return self::del($id, $val);
	}

	public static function count($id)
	{
		return Redis::zcount(self::CACHE_KEY . $id, '-inf', '+inf');
	}

	public static function getNotification($id)
	{
		return Cache::remember('service:notification:'.$id, now()->addMonths(3), function() use($id) {
			$n = Notification::with('item')->findOrFail($id);
			$fractal = new Fractal\Manager();
			$fractal->setSerializer(new ArraySerializer());
			$resource = new Fractal\Resource\Item($n, new NotificationTransformer());
			return $fractal->createData($resource)->toArray();
		});
	}

	public static function setNotification(Notification $notification)
	{
		return Cache::remember('service:notification:'.$notification->id, now()->addMonths(3), function() use($notification) {
			$fractal = new Fractal\Manager();
			$fractal->setSerializer(new ArraySerializer());
			$resource = new Fractal\Resource\Item($notification, new NotificationTransformer());
			return $fractal->createData($resource)->toArray();
		});
	} 

	public static function warmCache($id, $stop = 400, $force = false)
	{
		if(self::count($id) == 0 || $force == true) {
			$ids = Notification::whereProfileId($id)
				->latest()
				->limit($stop)
				->pluck('id');
			foreach($ids as $key) {
				self::set($id, $key);
			}
			return 1;
		}
		return 0;
	}
}
