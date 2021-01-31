<?php

namespace App\Services;

use Cache;
use Illuminate\Support\Facades\Redis;
use App\{Status, StatusHashtag};
use App\Transformer\Api\StatusHashtagTransformer;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

class StatusHashtagService {

	const CACHE_KEY = 'pf:services:status-hashtag:collection:';

	public static function get($id, $page = 1, $stop = 9)
	{
		if($page > 20) {
			return [];
		}

		return StatusHashtag::whereHashtagId($id)
			->whereStatusVisibility('public')
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
			->whereStatusVisibility('public')
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
		return 1;
	}

	public static function del($key)
	{
		return 1;
	}

	public static function count($id)
	{
		$key = 'pf:services:status-hashtag:count:' . $id;
		$ttl = now()->addMinutes(5);
		return Cache::remember($key, $ttl, function() use($id) {
			return StatusHashtag::whereHashtagId($id)->count();
		});
	}

	public static function getStatus($statusId, $hashtagId)
	{
		return ['status' => StatusService::get($statusId)];
	}
}