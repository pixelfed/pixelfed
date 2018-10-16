<?php

namespace App\Http\Controllers;

use App\Follower;
use App\Profile;
use App\Status;
use App\User;
use App\UserFilter;
use Auth;

class TimelineController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('twofactor');
    }

    public function personal()
    {
        $pid = Auth::user()->profile->id;
        // TODO: Use redis for timelines
        $following = Follower::whereProfileId($pid)->pluck('following_id');
        $following->push($pid);
        $filtered = UserFilter::whereUserId($pid)
                  ->whereFilterableType('App\Profile')
                  ->whereIn('filter_type', ['mute', 'block'])
                  ->pluck('filterable_id');
        $timeline = Status::whereIn('profile_id', $following)
                  ->whereNotIn('profile_id', $filtered)
                  ->whereVisibility('public')
                  ->orderBy('created_at', 'desc')
                  ->withCount(['comments', 'likes'])
                  ->simplePaginate(20);
        $type = 'personal';

        return view('timeline.template', compact('timeline', 'type'));
    }

    public function local()
    {
        // TODO: Use redis for timelines
        // $timeline = Timeline::build()->local();
        $pid = Auth::user()->profile->id;

        $filtered = UserFilter::whereUserId($pid)
                  ->whereFilterableType('App\Profile')
                  ->whereIn('filter_type', ['mute', 'block'])
                  ->pluck('filterable_id');
        $private = Profile::whereIsPrivate(true)->pluck('id');
        $filtered = $filtered->merge($private);
        $timeline = Status::whereHas('media')
                  ->whereNotIn('profile_id', $filtered)
                  ->whereNull('in_reply_to_id')
                  ->whereNull('reblog_of_id')
                  ->whereVisibility('public')
                  ->withCount(['comments', 'likes'])
                  ->orderBy('created_at', 'desc')
                  ->simplePaginate(20);
        $type = 'local';

        return view('timeline.template', compact('timeline', 'type'));
    }
}
