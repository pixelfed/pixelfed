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
		$this->validate($request, [
			'mode' => 'nullable|string|in:following,followers,hashtags'
		]);

		$mode = $request->input('mode') ?? 'followers';
		$profile = Auth::user()->profile;

		$data = null;
		switch ($mode) {
			case 'following':
				$data = $profile->following()->simplePaginate(10);
				break;

			case 'followers':
				$data = $profile->followers()->simplePaginate(10);
				break;

			case 'hashtags':
				$data = $profile->hashtagFollowing()->with('hashtag')->simplePaginate(10);
				break;
		}

		return view('settings.relationships.home', compact('profile', 'mode', 'data'));
	}

}