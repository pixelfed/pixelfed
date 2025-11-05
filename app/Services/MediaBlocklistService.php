<?php

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
}