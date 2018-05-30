<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{Hashtag, Follower, Profile, Status, StatusHashtag};
use Auth;

class DiscoverController extends Controller
{
    public function __construct()
    {
      $this->middleware('auth');
    }

    public function home()
    {
      $following = Follower::whereProfileId(Auth::user()->profile->id)->pluck('following_id');
      $people = Profile::whereNotIn('id', $following)->orderByRaw('rand()')->take(3)->get();
      $posts = Status::whereHas('media')->whereNotIn('profile_id', $following)->orderBy('created_at', 'desc')->take('21')->get();
      return view('discover.home', compact('people', 'posts'));
    }

    public function showTags(Request $request, $hashtag)
    {
      $tag = Hashtag::whereSlug($hashtag)->firstOrFail();
      $posts = $tag->posts()->has('media')->orderBy('id','desc')->paginate(12);
      $count = $tag->posts()->has('media')->orderBy('id','desc')->count();
      return view('discover.tags.show', compact('tag', 'posts', 'count'));
    }
}
