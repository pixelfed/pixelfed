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
		return view('settings.relationships.home');
	}

}