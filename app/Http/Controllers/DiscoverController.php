<?php

namespace App\Http\Controllers;

use App\Follower;
use App\Hashtag;
use App\Profile;
use App\Status;
use App\UserFilter;
use Auth;
use Illuminate\Http\Request;

class DiscoverController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function home()
    {
        $pid = Auth::user()->profile->id;

        $following = Follower::whereProfileId($pid)
          ->pluck('following_id');

        $filtered = UserFilter::whereUserId($pid)
                  ->whereFilterableType('App\Profile')
                  ->whereIn('filter_type', ['mute', 'block'])
                  ->pluck('filterable_id');
        $following->push($pid);
        $following->push($filtered);

        $people = Profile::inRandomOrder()
          ->whereNotIn('id', $following)
          ->take(3)
          ->get();

        $posts = Status::whereHas('media')
          ->where('profile_id', '!=', $pid)
          ->whereNotIn('profile_id', $following)
          ->orderBy('created_at', 'desc')
          ->simplePaginate(21);

        return view('discover.home', compact('people', 'posts'));
    }

    public function showTags(Request $request, $hashtag)
    {
        $this->validate($request, [
          'page' => 'nullable|integer|min:1|max:10',
      ]);

        $tag = Hashtag::with('posts')
          ->withCount('posts')
          ->whereSlug($hashtag)
          ->firstOrFail();

        $posts = $tag->posts()
          ->whereIsNsfw(false)
          ->whereVisibility('public')
          ->has('media')
          ->orderBy('id', 'desc')
          ->simplePaginate(12);

        return view('discover.tags.show', compact('tag', 'posts'));
    }
}
