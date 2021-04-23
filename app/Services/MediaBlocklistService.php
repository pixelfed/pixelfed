<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Services;

use Cache;
use Illuminate\Support\Facades\File;
use App\Media;
use App\MediaBlocklist;

class MediaBlocklistService
{
	public static function get()
	{
		return MediaBlocklist::whereActive(true)
			->pluck('sha256')
			->toArray();
	}

	public static function exists($hash)
	{
		$hashes = self::get();
		return in_array($hash, $hashes) == true;
	}

	public static function remove($hash)
	{
		if(!self::exists($hash)) {
			return;
		}
		MediaBlocklist::whereSha256($hash)->delete();
		return;
	}

	public static function add($hash, $metadata)
	{
		$m = new MediaBlocklist;
		$m->sha256 = $hash;
		$m->active = true;
		$m->metadata = json_encode($metadata);
		$m->save();

		return $m;
	}
}
