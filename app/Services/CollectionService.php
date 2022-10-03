<?php

namespace App\Services;

use App\Collection;
use App\CollectionItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CollectionService
{
	const CACHE_KEY = 'pf:services:collections-v1:';

	public static function getItems($id, $start = 0, $stop = 10)
	{
		if(self::count($id)) {
			return Redis::zrangebyscore(self::CACHE_KEY . 'items:' . $id, $start, $stop);
		}

		return self::coldBootItems($id);
	}

	public static function addItem($id, $sid, $score)
	{
		return Redis::zadd(self::CACHE_KEY . 'items:' . $id, $score, $sid);
	}

	public static function removeItem($id, $sid)
	{
		return Redis::zrem(self::CACHE_KEY . 'items:' . $id, $sid);
	}

	public static function clearItems($id)
	{
		return Redis::del(self::CACHE_KEY . 'items:' . $id);
	}

	public static function coldBootItems($id)
	{
		return CollectionItem::whereCollectionId($id)
			->orderBy('order')
			->get()
			->each(function($item) use ($id) {
				return self::addItem($id, $item->object_id, $item->order);
			})
			->map(function($item) {
				return (string) $item->object_id;
			})
			->values()
			->toArray();
	}

	public static function count($id)
	{
		$count = Redis::zcard(self::CACHE_KEY . 'items:' . $id);
		if(!$count) {
			self::coldBootItems($id);
			$count = Redis::zcard(self::CACHE_KEY . 'items:' . $id);
		}
		return $count;
	}

	public static function getCollection($id)
	{
		$collection = Cache::remember(self::CACHE_KEY . 'get:' . $id, 86400, function() use($id) {
			$collection = Collection::find($id);
			if(!$collection) {
				return false;
			}
			$account = AccountService::get($collection->profile_id);
			if(!$account) {
				return false;
			}
			return [
				'id' => (string) $collection->id,
				'pid' => (string) $collection->profile_id,
				'visibility' => $collection->visibility,
				'title' => $collection->title,
				'description' => $collection->description,
				'thumb' => url('/storage/no-preview.png'),
				'url' => $collection->url(),
				'updated_at' => $collection->updated_at,
				'published_at' => $collection->published_at,
			];
		});

		if($collection) {
			$account = AccountService::get($collection['pid']);
			if(!$account) {
				return false;
			}
			$collection['avatar'] = $account['avatar'];
			$collection['username'] = $account['username'];
			$collection['thumb'] = self::getThumb($id);
			$collection['post_count'] = self::count($id);
		}

		return $collection;
	}

	public static function setCollection($id, $collection)
	{
		$account = AccountService::get($collection->profile_id);
		if(!$account) {
			return false;
		}
		$res = [
			'id' => (string) $collection->id,
			'pid' => (string) $collection->profile_id,
			'visibility' => $collection->visibility,
			'title' => $collection->title,
			'description' => $collection->description,
			'thumb' => self::getThumb($id),
			'url' => $collection->url(),
			'updated_at' => $collection->updated_at,
			'published_at' => $collection->published_at,
		];
		Cache::put(self::CACHE_KEY . 'get:' . $id, $res, 86400);
		$res['avatar'] = $account['avatar'];
		$res['username'] = $account['username'];
		$res['post_count'] = self::count($id);
		return $res;
	}

	public static function deleteCollection($id)
	{
		Redis::del(self::CACHE_KEY . 'items:' . $id);
		Cache::forget(self::CACHE_KEY . 'get:' . $id);
	}

	public static function getThumb($id)
	{
		$item = self::getItems($id, 0, 1);
		if(!$item || empty($item)) {
			return url('/storage/no-preview.png');
		}
		$status = StatusService::get($item[0]);
		if(!$status) {
			return url('/storage/no-preview.png');
		}

		if(!isset($status['media_attachments']) || empty($status['media_attachments'])) {
			return url('/storage/no-preview.png');
		}

		return $status['media_attachments'][0]['url'];
	}
}
