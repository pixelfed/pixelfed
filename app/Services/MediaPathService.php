<?php

namespace App\Services;

use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use App\Media;
use App\Profile;
use App\User;

class MediaPathService {

	public static function get($account, $version = 1)
	{
		$mh = hash('sha256', date('Y').'-.-'.date('m'));
		
		if($account instanceOf User) {
			switch ($version) {
				// deprecated
				case 1:
					$monthHash = hash('sha1', date('Y').date('m'));
					$userHash = hash('sha1', $account->id . (string) $account->created_at);
					$path = "public/m/{$monthHash}/{$userHash}";
					break;

				case 2:
					$monthHash = substr($mh, 0, 9).'-'.substr($mh, 9, 6);
					$userHash = $account->profile_id;
					$random = Str::random(12);
					$path = "public/m/_v2/{$userHash}/{$monthHash}/{$random}";
					break;
				
				default:
					$monthHash = substr($mh, 0, 9).'-'.substr($mh, 9, 6);
					$userHash = $account->profile_id;
					$random = Str::random(12);
					$path = "public/m/_v2/{$userHash}/{$monthHash}/{$random}";
					break;
			}
		} 
		if($account instanceOf Profile) {
			$monthHash = substr($mh, 0, 9).'-'.substr($mh, 9, 6);
			$userHash = $account->id;
			$random = Str::random(12);
			$path = "public/m/_v2/{$userHash}/{$monthHash}/{$random}";
		}
		return $path;
	}

	public static function story($account, $version = 1)
	{
		$mh = hash('sha256', date('Y').'-.-'.date('m'));
		$monthHash = date('Y').date('m').substr($mh, 0, 6).substr($mh, 58, 6);
		$random = '03'.Str::random(random_int(6,9)).'_'.Str::random(random_int(6,17));

		if($account instanceOf User) {
			switch ($version) {
				case 1:
					$userHash = $account->profile_id;
					$path = "public/_esm.t3/{$monthHash}/{$userHash}/{$random}";
					break;
				
				default:
					$userHash = $account->profile_id;
					$path = "public/_esm.t3/{$monthHash}/{$userHash}/{$random}";
					break;
			}
		} 
		if($account instanceOf Profile) {
			$userHash = $account->id;
			$path = "public/_esm.t3/{$monthHash}/{$userHash}/{$random}";
		}
		return $path;
	}

}