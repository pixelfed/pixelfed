<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Cache;

class SnowflakeService {

	public static function byDate(Carbon $ts = null)
	{
		$seq = Cache::get('snowflake:seq');

		if(!$seq) {
			Cache::put('snowflake:seq', 1);
			$seq = 1;
		} else {
			Cache::increment('snowflake:seq');
		}

		if($seq >= 4095) {
			$seq = 0;
			Cache::put('snowflake:seq', 0);
		}

		if($ts == null) {
			$ts = microtime(true);
		}

		if($ts instanceOf Carbon) {
			$ts = now()->parse($ts)->timestamp;
		}

		return ((round($ts * 1000) - 1549756800000) << 22)
		| (random_int(1,31) << 17)
		| (random_int(1,31) << 12)
		| $seq;
	}

}
