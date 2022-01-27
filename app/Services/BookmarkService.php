<?php

namespace App\Services;

use App\Bookmark;
use Illuminate\Support\Facades\Cache;

class BookmarkService
{
	public static function get($profileId, $statusId)
	{
		// return Cache::remember('pf:bookmarks:' . $profileId . ':' . $statusId, 84600, function() use($profileId, $statusId) {
			return Bookmark::whereProfileId($profileId)->whereStatusId($statusId)->exists();
		// });
	}
}
