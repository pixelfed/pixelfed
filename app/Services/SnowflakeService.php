<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */
 
namespace App\Services;

use Illuminate\Support\Carbon;

class SnowflakeService {

	public static function byDate(Carbon $ts = null)
	{
		$ts = $ts ? now()->parse($ts)->timestamp : microtime(true);
		return ((round($ts * 1000) - 1549756800000) << 22)
		| (1 << 17)
		| (1 << 12)
		| 0;
	}

}
