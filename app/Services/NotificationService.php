<?php

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
	const MASTODON_TYPES = [
		'follow',
		'follow_request',
		'mention',
		'reblog',
		'favourite',
		'poll',
		'status'
	];

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
			$n = self::getNotification($id);
			if($n != null) {
				$res->push($n);
			}
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

	public static function getMax($id = false, $start = 0, $limit = 10)
	{
		$ids = self::getRankedMaxId($id, $start, $limit);

		if(empty($ids)) {
			return [];
		}

		$res = collect([]);
		foreach($ids as $id) {
			$n = self::getNotification($id);
			if($n != null) {
				$res->push($n);
			}
		}
		return $res->toArray();
	}

	public static function getMin($id = false, $start = 0, $limit = 10)
	{
		$ids = self::getRankedMinId($id, $start, $limit);

		if(empty($ids)) {
			return [];
		}

		$res = collect([]);
		foreach($ids as $id) {
			$n = self::getNotification($id);
			if($n != null) {
				$res->push($n);
			}
		}
		return $res->toArray();
	}


	public static function getMaxMastodon($id = false, $start = 0, $limit = 10)
	{
		$ids = self::getRankedMaxId($id, $start, $limit);

		if(empty($ids)) {
			return [];
		}

		$res = collect([]);
		foreach($ids as $id) {
			$n = self::getNotification($id);
			if($n != null && in_array($n['type'], self::MASTODON_TYPES)) {
				$n['account'] = AccountService::getMastodon($n['account']['id']);

				if(isset($n['relationship'])) {
					unset($n['relationship']);
				}

				if(isset($n['status'])) {
					$n['status'] = StatusService::getMastodon($n['status']['id'], false);
				}

				$res->push($n);
			}
		}
		return $res->toArray();
	}

	public static function getMinMastodon($id = false, $start = 0, $limit = 10)
	{
		$ids = self::getRankedMinId($id, $start, $limit);

		if(empty($ids)) {
			return [];
		}

		$res = collect([]);
		foreach($ids as $id) {
			$n = self::getNotification($id);
			if($n != null && in_array($n['type'], self::MASTODON_TYPES)) {
				$n['account'] = AccountService::getMastodon($n['account']['id']);

				if(isset($n['relationship'])) {
					unset($n['relationship']);
				}

				if(isset($n['status'])) {
					$n['status'] = StatusService::getMastodon($n['status']['id'], false);
				}

				$res->push($n);
			}
		}
		return $res->toArray();
	}

	public static function getRankedMaxId($id = false, $start = null, $limit = 10)
	{
		if(!$start || !$id) {
			return [];
		}

		return array_keys(Redis::zrevrangebyscore(self::CACHE_KEY.$id, $start, '-inf', [
			'withscores' => true,
			'limit' => [1, $limit]
		]));
	}

	public static function getRankedMinId($id = false, $end = null, $limit = 10)
	{
		if(!$end || !$id) {
			return [];
		}

		return array_keys(Redis::zrevrangebyscore(self::CACHE_KEY.$id, '+inf', $end, [
			'withscores' => true,
			'limit' => [0, $limit]
		]));
	}

	public static function set($id, $val)
	{
		return Redis::zadd(self::CACHE_KEY . $id, $val, $val);
	}

	public static function del($id, $val)
	{
		Cache::forget('service:notification:' . $val);
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
		return Cache::remember('service:notification:'.$id, 86400, function() use($id) {
			$n = Notification::with('item')->find($id);

			if(!$n) {
				return null;
			}

			$account = AccountService::get($n->actor_id, true);

			if(!$account) {
				return null;
			}

			$fractal = new Fractal\Manager();
			$fractal->setSerializer(new ArraySerializer());
			$resource = new Fractal\Resource\Item($n, new NotificationTransformer());
			return $fractal->createData($resource)->toArray();
		});
	}

	public static function setNotification(Notification $notification)
	{
		return Cache::remember('service:notification:'.$notification->id, now()->addDays(3), function() use($notification) {
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
