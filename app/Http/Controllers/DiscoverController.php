<?php

namespace App\Http\Controllers;

use App\{
  DiscoverCategory,
  Follower,
  Hashtag,
  Profile,
  Status, 
  StatusHashtag, 
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
        return view('discover.home');
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
          ->whereNull('url')
          ->whereNull('uri')
          ->whereHas('media')
          ->withCount(['likes', 'comments'])
          ->whereIsNsfw(false)
          ->whereVisibility('public')
          ->orderBy('id', 'desc')
          ->simplePaginate(12);

        if($posts->count() == 0) {
          abort(404);
        }
        
        return view('discover.tags.show', compact('tag', 'posts'));
    }

    public function showCategory(Request $request, $slug)
    {
      $tag = DiscoverCategory::whereActive(true)
        ->whereSlug($slug)
        ->firstOrFail();

      // todo refactor this mess
      $tagids = $tag->hashtags->pluck('id')->toArray();
      $sids = StatusHashtag::whereIn('hashtag_id', $tagids)->orderByDesc('status_id')->take(500)->pluck('status_id')->toArray();
      $posts = Status::whereIn('id', $sids)->whereNull('uri')->whereType('photo')->whereNull('in_reply_to_id')->whereNull('reblog_of_id')->orderByDesc('created_at')->paginate(21);
      $tag->posts_count = $tag->posts()->count();
      return view('discover.tags.category', compact('tag', 'posts'));
    }

    public function showPersonal(Request $request)
    {
      $profile = Auth::user()->profile;
      // todo refactor this mess
      $tags = Hashtag::whereHas('posts')->orderByRaw('rand()')->take(5)->get();
      $following = $profile->following->pluck('id');
      $following = $following->push($profile->id)->toArray();
      $posts = Status::withCount(['likes','comments'])->whereNotIn('profile_id', $following)->whereHas('media')->whereType('photo')->orderByDesc('created_at')->paginate(21);
      $posts->post_count = Status::whereNotIn('profile_id', $following)->whereHas('media')->whereType('photo')->count();
      return view('discover.personal', compact('posts', 'tags'));
    }
}
