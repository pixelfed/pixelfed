<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use App\{
	Profile,
	Status,
	UserFilter
};

class PublicTimelineService {

	const CACHE_KEY = 'pf:services:timeline:public';

	public static function get($start = 0, $stop = 10)
	{
		if($stop > 100) {
			$stop = 100;
		}
		$tl = [];
		$keys = Redis::zrevrange(self::CACHE_KEY, $start, $stop);
		foreach($keys as $key) {
			array_push($tl, StatusService::get($key));
		}
		return $tl;
	}

	public static function add($val)
	{
		return Redis::zadd(self::CACHE_KEY, 1, $val);
	}

	public static function rem($val)
	{
		return Redis::zrem(self::CACHE_KEY, $val);
	}

	public static function del($val)
	{
		return self::rem($val);
	}

	public static function count()
	{
		return Redis::zcount(self::CACHE_KEY, '-inf', '+inf');
	}

	public static function warmCache($force = false, $limit = 100)
	{
		if(self::count() == 0 || $force == true) {
			$ids = Status::whereNull('uri')
				->whereNull('in_reply_to_id')
				->whereNull('reblog_of_id')
				->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
				->whereScope('public')
				->latest()
				->limit($limit)
				->pluck('id');
			foreach($ids as $id) {
				self::add($id);
			}
			return 1;
		}
		return 0;
	}
}