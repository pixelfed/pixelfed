<?php

namespace App\Http\Controllers;

use Auth;
use Cache;
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
        $this->validate($request, [
            'layout' => 'nullable|string|in:grid,feed'
        ]);
        $layout = $request->input('layout', 'feed');
        return view('timeline.local', compact('layout'));
    }

    public function network(Request $request)
    {
        $this->validate($request, [
            'layout' => 'nullable|string|in:grid,feed'
        ]);
        $layout = $request->input('layout', 'feed');
        return view('timeline.network', compact('layout'));
    }
}
