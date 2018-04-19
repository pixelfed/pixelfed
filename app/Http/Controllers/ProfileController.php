<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{Profile};

class ProfileController extends Controller
{
    public function show(Request $request, $username)
    {
      $user = Profile::whereUsername($username)->firstOrFail();
      $timeline = $user->statuses()->orderBy('id','desc')->paginate(10);
      return view('profile.show', compact('user', 'timeline'));
    }
}
