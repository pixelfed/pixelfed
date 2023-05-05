<?php

namespace App\Services;

use Cache;
use App\Profile;

class AvatarService
{
	public static function get($profile_id)
	{
		$exists = Cache::get('avatar:' . $profile_id);
		if($exists) {
			return $exists;
		}

		$profile = Profile::find($profile_id);
		if(!$profile) {
			return config('app.url') . '/storage/avatars/default.jpg';
		}
		return $profile->avatarUrl();
	}
}
