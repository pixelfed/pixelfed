<?php

namespace App\Services;

use Cache;
use App\Hashtag;
use App\StatusHashtag;

class HashtagService {

	public static function get($id)
	{
		return Cache::remember('services:hashtag:by_id:' . $id, 3600, function() use($id) {
			$tag = Hashtag::find($id);
			if(!$tag) {
				return [];
			}
			return [
				'name' => $tag->name,
				'slug' => $tag->slug,
			];
		});
	}

	public static function count($id)
	{
		return Cache::remember('services:hashtag:count:by_id:' . $id, 3600, function() use($id) {
			return StatusHashtag::whereHashtagId($id)->count();
		});
	}

}
