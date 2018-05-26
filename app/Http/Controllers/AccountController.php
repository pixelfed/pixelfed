<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth, Cache;
use App\{Notification, Profile, User};

class AccountController extends Controller
{
    public function __construct()
    {
      $this->middleware('auth');
    }

    public function notifications(Request $request)
    {
      $user = Auth::user();
      $profile = $user->profile;
      return view('account.activity', compact('profile'));
    }
}
