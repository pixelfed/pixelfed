<?php

namespace App\Http\Controllers;

use App\{
  Follower,
  Hashtag,
  Profile,
  Status, 
  UserFilter
};
use Auth, DB, Cache;
use Illuminate\Http\Request;

class DiscoverController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function home(Request $request)
    {
        $this->validate($request, [
          'page' => 'nullable|integer|max:50'
        ]);

        $pid = Auth::user()->profile->id;

        $following = Cache::remember('feature:discover:following:'.$pid, 720, function() use($pid) {
          $following = Follower::select('following_id')
                      ->whereProfileId($pid)
                      ->pluck('following_id');
          $filtered = UserFilter::select('filterable_id')
                    ->whereUserId($pid)
                    ->whereFilterableType('App\Profile')
                    ->whereIn('filter_type', ['mute', 'block'])
                    ->pluck('filterable_id');
          $following->push($pid);
          
          if($filtered->count() > 0) {
            $following->push($filtered);
          }
          return $following;
        });

        $people = Cache::remember('feature:discover:people:'.$pid, 15, function() use($following) {
            return Profile::select('id', 'name', 'username')->inRandomOrder()
                ->whereHas('statuses')
                ->whereNull('domain')
                ->whereNotIn('id', $following)
                ->whereIsPrivate(false)
                ->take(3)
                ->get();
        });

        $posts = Status::select('id', 'caption', 'profile_id')
          ->whereHas('media')
          ->whereHas('profile', function($q) {
            $q->where('is_private', false);
          })
          ->whereIsNsfw(false)
          ->whereVisibility('public')
          ->where('profile_id', '<>', $pid)
          ->whereNotIn('profile_id', $following)
          ->withCount(['comments', 'likes'])
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
          ->withCount(['likes', 'comments'])
          ->whereIsNsfw(false)
          ->whereVisibility('public')
          ->has('media')
          ->orderBy('id', 'desc')
          ->simplePaginate(12);

        return view('discover.tags.show', compact('tag', 'posts'));
    }
}
