<?php

namespace App\Services;

use Cache;
use Illuminate\Support\Facades\Redis;
use App\Media;
use App\Status;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use App\Transformer\Api\MediaTransformer;
use App\Util\Media\License;

class MediaService
{
	const CACHE_KEY = 'status:transformer:media:attachments:';

	public static function get($statusId)
	{
		return Cache::remember(self::CACHE_KEY.$statusId, 129600, function() use($statusId) {
			$status = Status::find($statusId);
			if(!$status) {
				return [];
			}
			if(in_array($status->type, ['photo', 'video', 'video:album', 'photo:album', 'loop', 'photo:video:album'])) {
				$media = Media::whereStatusId($status->id)->orderBy('order')->get();
				$fractal = new Fractal\Manager();
				$fractal->setSerializer(new ArraySerializer());
				$resource = new Fractal\Resource\Collection($media, new MediaTransformer());
				return $fractal->createData($resource)->toArray();
			}
			return [];
		});
	}

	public static function del($statusId)
	{
		return Cache::forget(self::CACHE_KEY . $statusId);
	}

	public static function activitypub($statusId)
	{
		$status = self::get($statusId);
		if(!$status) {
			return [];
		}

		return collect($status)->map(function($s) use($license) {
			$license = isset($s['license']) && $s['license']['title'] ? $s['license']['title'] : null;
			return [
				'type'      => 'Document',
				'mediaType' => $s['mime'],
				'url'       => $s['url'],
				'name'      => $s['description'],
				'blurhash'  => $s['blurhash'],
				'license'   => $license
			];
		});
	}
}
