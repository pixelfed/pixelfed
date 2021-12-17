<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use DB;
use App\Status;
//use App\Transformer\Api\v3\StatusTransformer;
use App\Transformer\Api\StatusStatelessTransformer;
use App\Transformer\Api\StatusTransformer;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

class StatusService
{
	const CACHE_KEY = 'pf:services:status:';

	public static function key($id, $publicOnly = true)
	{
		$p = $publicOnly ? 'pub:' : 'all:';
		return self::CACHE_KEY . $p . $id;
	}

	public static function get($id, $publicOnly = true)
	{
		return Cache::remember(self::key($id, $publicOnly), now()->addDays(7), function() use($id, $publicOnly) {
			if($publicOnly) {
				$status = Status::whereScope('public')->find($id);
			} else {
				$status = Status::whereIn('scope', ['public', 'private', 'unlisted', 'group'])->find($id);
			}
			if(!$status) {
				return null;
			}
			$fractal = new Fractal\Manager();
			$fractal->setSerializer(new ArraySerializer());
			$resource = new Fractal\Resource\Item($status, new StatusStatelessTransformer());
			return $fractal->createData($resource)->toArray();
		});
	}

	public static function getFull($id, $pid, $publicOnly = true)
	{
		$res = self::get($id, $publicOnly);
		$res['relationship'] = RelationshipService::get($pid, $res['account']['id']);
		return $res;
	}

	public static function getDirectMessage($id)
	{
		$status = Status::whereScope('direct')->find($id);

		if(!$status) {
			return null;
		}

		$fractal = new Fractal\Manager();
		$fractal->setSerializer(new ArraySerializer());
		$resource = new Fractal\Resource\Item($status, new StatusTransformer());
		return $fractal->createData($resource)->toArray();
	}

	public static function del($id, $purge = false)
	{
		$status = self::get($id);

		if($purge) {
			if($status && isset($status['account']) && isset($status['account']['id'])) {
				Cache::forget('profile:embed:' . $status['account']['id']);
			}
			Cache::forget('status:transformer:media:attachments:' . $id);
			MediaService::del($id);
			Cache::forget('status:thumb:nsfw0' . $id);
			Cache::forget('status:thumb:nsfw1' . $id);
			Cache::forget('pf:services:sh:id:' . $id);
			PublicTimelineService::rem($id);
		}

		Cache::forget(self::key($id, false));
		return Cache::forget(self::key($id));
	}

	public static function refresh($id)
	{
		Cache::forget(self::key($id, false));
		Cache::forget(self::key($id, true));
		self::get($id, false);
		self::get($id, true);
	}
}
