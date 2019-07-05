<?php

namespace App\Services;

use Cache, Redis;
use App\{Status, StatusHashtag};
use App\Transformer\Api\StatusHashtagTransformer;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

class StatusHashtagService {

	const CACHE_KEY = 'pf:services:status-hashtag:collection:';

	public static function get($id, $page = 1, $stop = 9)
	{
		return StatusHashtag::whereHashtagId($id)
			->whereHas('media')
			->skip($stop)
			->latest()
			->take(9)
			->pluck('status_id')
			->map(function ($i, $k) use ($id) {
				return self::getStatus($i, $id);
			})
			->all();
	}

	public static function coldGet($id, $start = 0, $stop = 2000)
	{
		$stop = $stop > 2000 ? 2000 : $stop;
		$ids = StatusHashtag::whereHashtagId($id)
			->whereHas('media')
			->latest()
			->skip($start)
			->take($stop)
			->pluck('status_id');
		foreach($ids as $key) {
			self::set($id, $key);
		}
		return $ids;
	}

	public static function set($key, $val)
	{
		return Redis::zadd(self::CACHE_KEY . $key, $val, $val);
	}

	public static function del($key)
	{
		return Redis::zrem(self::CACHE_KEY . $key, $val);
	}

	public static function count($id)
	{
		$count = Redis::zcount(self::CACHE_KEY . $id, '-inf', '+inf');
		if(empty($count)) {
			$count = StatusHashtag::whereHashtagId($id)->count();
		}
		return $count;
	}

	public static function getStatus($statusId, $hashtagId)
	{
		return Cache::remember('pf:services:status-hashtag:post:'.$statusId.':hashtag:'.$hashtagId, now()->addMonths(3), function() use($statusId, $hashtagId) {
			$statusHashtag = StatusHashtag::with('profile', 'status', 'hashtag')
				->whereStatusId($statusId)
				->whereHashtagId($hashtagId)
				->first();
			$fractal = new Fractal\Manager();
			$fractal->setSerializer(new ArraySerializer());
			$resource = new Fractal\Resource\Item($statusHashtag, new StatusHashtagTransformer());
			return $fractal->createData($resource)->toArray();
		});
	}
}