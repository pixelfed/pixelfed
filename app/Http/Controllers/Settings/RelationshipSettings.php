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

	public function relationshipsHome()
	{
		$profile = Auth::user()->profile;
		$following = $profile->following()->simplePaginate(10);
		$followers = $profile->followers()->simplePaginate(10);

		return view('settings.relationships.home', compact('profile', 'following', 'followers'));
	}

}