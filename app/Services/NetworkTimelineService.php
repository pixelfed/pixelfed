<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use App\{
	Profile,
	Status,
	UserFilter
};

class NetworkTimelineService
{
	const CACHE_KEY = 'pf:services:timeline:network';

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
		if(self::count() > config('instance.timeline.network.cache_dropoff')) {
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
            $filteredDomains = collect(InstanceService::getBannedDomains())
                ->merge(InstanceService::getUnlistedDomains())
                ->unique()
                ->values()
                ->toArray();
			$ids = Status::whereNotNull('uri')
				->whereScope('public')
				->when($hideNsfw, function($q, $hideNsfw) {
                  return $q->where('is_nsfw', false);
                })
				->whereNull('in_reply_to_id')
				->whereNull('reblog_of_id')
				->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
				->where('created_at', '>', now()->subHours(config('instance.timeline.network.max_hours_old')))
				->orderByDesc('created_at')
				->limit($limit)
				->pluck('uri', 'id');
            $ids = $ids->filter(function($k, $v) use($filteredDomains) {
                $domain = parse_url($k, PHP_URL_HOST);
                return !in_array($domain, $filteredDomains);
            })->map(function($k, $v) {
                return $v;
            })->flatten();
			foreach($ids as $id) {
				self::add($id);
			}
			return 1;
		}
		return 0;
	}
}
