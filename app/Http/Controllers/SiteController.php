<?php

namespace App\Http\Controllers;

use App, Auth;
use Illuminate\Http\Request;
use App\{Follower, Status, User};

class SiteController extends Controller
{

    public function home()
    {
        if(Auth::check()) {
          return $this->homeTimeline();
        } else {
          return $this->homeGuest();
        }
    }

    public function homeGuest()
    {
        return view('site.index');
    }

    public function homeTimeline()
    {
      // TODO: Use redis for timelines
      $following = Follower::whereProfileId(Auth::user()->profile->id)->pluck('following_id');
      $following->push(Auth::user()->profile->id);
      $timeline = Status::whereIn('profile_id', $following)
                  ->orderBy('id','desc')
                  ->withCount(['comments', 'likes', 'shares'])
                  ->simplePaginate(10);
      return view('timeline.template', compact('timeline'));
    }

    public function changeLocale(Request $request, $locale)
    {
        if(!App::isLocale($locale)) {
          return redirect()->back();
        }
        App::setLocale($locale);
        return redirect()->back();
    }
}
