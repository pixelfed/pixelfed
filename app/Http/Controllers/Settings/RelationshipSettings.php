<?php

namespace App\Http\Controllers\Settings;

use App\AccountLog;
use App\EmailVerification;
use App\Instance;
use App\Media;
use App\Profile;
use App\User;
use App\UserFilter;
use App\Util\Lexer\PrettyNumber;
use Auth, Cache, DB;
use Illuminate\Http\Request;

trait RelationshipSettings
{

	public function relationshipsHome(Request $request)
	{
		$mode = $request->input('mode') == 'following' ? 'following' : 'followers';
		$profile = Auth::user()->profile;

		$following = $followers = [];

		if($mode == 'following') {
			$data = $profile->following()->simplePaginate(10);
		} else {
			$data = $profile->followers()->simplePaginate(10);
		}

		return view('settings.relationships.home', compact('profile', 'mode', 'data'));
	}

}