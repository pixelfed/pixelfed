<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\{Follower, Profile, Status, User, UserFilter};

class TimelineController extends Controller
{
    public function __construct()
    {
      $this->middleware('auth');
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
                  ->orderBy('id','desc')
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
                  ->withCount(['comments', 'likes'])
                  ->orderBy('id','desc')
                  ->simplePaginate(20);
      $type = 'local';
      return view('timeline.template', compact('timeline', 'type'));
    }

}
