<?php

namespace App\Http\Controllers;

use Auth, Cache;
use App\Follower;
use App\Profile;
use App\Status;
use App\User;
use App\UserFilter;
use Illuminate\Http\Request;

class TimelineController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('twofactor');
    }

    public function local(Request $request)
    {
        return view('timeline.local');
    }
}
