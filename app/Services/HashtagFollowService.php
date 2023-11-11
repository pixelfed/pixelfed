<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Hashtag;
use App\StatusHashtag;
use App\HashtagFollow;

class HashtagFollowService
{
	const FOLLOW_KEY = 'pf:services:hashtag-follows:v1:';

	public static function getPidByHid($hid)
	{
		return Cache::remember(self::FOLLOW_KEY . $hid, 86400, function() use($hid) {
			return HashtagFollow::whereHashtagId($hid)->pluck('profile_id')->toArray();
		});
	}
}
