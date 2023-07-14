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

		return Redis::zrevrange(self::CACHE_KEY, $start, $stop);
	}

	public static function getRankedMaxId($start = null, $limit = 10)
	{
		if(!$start) {
			return [];
		}

		return array_keys(Redis::zrevrangebyscore(self::CACHE_KEY, $start, '-inf', [
			'withscores' => true,
			'limit' => [1, $limit]
		]));
	}

	public static function getRankedMinId($end = null, $limit = 10)
	{
		if(!$end) {
			return [];
		}

		return array_keys(Redis::zrevrangebyscore(self::CACHE_KEY, '+inf', $end, [
			'withscores' => true,
			'limit' => [0, $limit]
		]));
	}

	public static function add($val)
	{
		if(self::count() > 400) {
			Redis::zpopmin(self::CACHE_KEY);
		}

		return Redis::zadd(self::CACHE_KEY, $val, $val);
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
		return Redis::zcard(self::CACHE_KEY);
	}

    public static function deleteByProfileId($profileId)
    {
        $res = Redis::zrange(self::CACHE_KEY, 0, '-1');
        if(!$res) {
            return;
        }
        foreach($res as $postId) {
            $s = StatusService::get($postId);
            if(!$s) {
                self::rem($postId);
                continue;
            }
            if($s['account']['id'] == $profileId) {
                self::rem($postId);
            }
        }

        return;
    }

	public static function warmCache($force = false, $limit = 100)
	{
		if(self::count() == 0 || $force == true) {
			$hideNsfw = config('instance.hide_nsfw_on_public_feeds');
			Redis::del(self::CACHE_KEY);
			$minId = SnowflakeService::byDate(now()->subDays(14));
			$ids = Status::where('id', '>', $minId)
				->whereNull(['uri', 'in_reply_to_id', 'reblog_of_id'])
				->when($hideNsfw, function($q, $hideNsfw) {
                  return $q->where('is_nsfw', false);
                })
				->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
				->whereScope('public')
				->orderByDesc('id')
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
