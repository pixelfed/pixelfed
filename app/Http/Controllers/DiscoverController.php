<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Http\Controllers;

use App\{
  DiscoverCategory,
  Follower,
  Hashtag,
  HashtagFollow,
  Profile,
  Status, 
  StatusHashtag, 
  UserFilter
};
use Auth, DB, Cache;
use Illuminate\Http\Request;
use App\Transformer\Api\AccountTransformer;
use App\Transformer\Api\AccountWithStatusesTransformer;
use App\Transformer\Api\StatusTransformer;
use App\Transformer\Api\StatusStatelessTransformer;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use App\Services\StatusHashtagService;
use App\Services\SnowflakeService;
use App\Services\StatusService;

class DiscoverController extends Controller
{
    protected $fractal;

    public function __construct()
    {
        $this->fractal = new Fractal\Manager();
        $this->fractal->setSerializer(new ArraySerializer());
    }

    public function home(Request $request)
    {
        abort_if(!Auth::check() && config('instance.discover.public') == false, 403);
        return view('discover.home');
    }

    public function showTags(Request $request, $hashtag)
    {
        abort_if(!config('instance.discover.tags.is_public') && !Auth::check(), 403);

        $tag = Hashtag::whereName($hashtag)
          ->orWhere('slug', $hashtag)
          ->firstOrFail();
        $tagCount = StatusHashtagService::count($tag->id);
        return view('discover.tags.show', compact('tag', 'tagCount'));
    }

    public function showCategory(Request $request, $slug)
    {
      abort(404);
    }

    public function showLoops(Request $request)
    {
        abort(404);
    }

    public function loopsApi(Request $request)
    {
        abort(404);
    }

    public function loopWatch(Request $request)
    {
        return response()->json(200);
    }

    public function getHashtags(Request $request)
    {
      $auth = Auth::check();
      abort_if(!config('instance.discover.tags.is_public') && !$auth, 403);

      $this->validate($request, [
        'hashtag' => 'required|string|min:1|max:124',
        'page' => 'nullable|integer|min:1|max:' . ($auth ? 29 : 10)
      ]);

      $page = $request->input('page') ?? '1';
      $end = $page > 1 ? $page * 9 : 0;
      $tag = $request->input('hashtag');

      $hashtag = Hashtag::whereName($tag)->firstOrFail();
      if($page == 1) {
        $res['follows'] = HashtagFollow::whereUserId(Auth::id())
          ->whereHashtagId($hashtag->id)
          ->exists();
      }
      $res['hashtag'] = [
        'name' => $hashtag->name,
        'url' => $hashtag->url()
      ];
      $res['tags'] = StatusHashtagService::get($hashtag->id, $page, $end);
      return $res;
    }

    public function profilesDirectory(Request $request)
    {
      return redirect('/')
        ->with('statusRedirect', 'The Profile Directory is unavailable at this time.');
    }

    public function profilesDirectoryApi(Request $request)
    {
      return ['error' => 'Temporarily unavailable.'];
    }

    public function trendingApi(Request $request)
    {
      abort_if(config('instance.discover.public') == false && !Auth::check(), 403);

      $this->validate($request, [
        'range' => 'nullable|string|in:daily,monthly'
      ]);

      $range = $request->input('range') == 'monthly' ? 31 : 1;

      $key = ':api:discover:trending:v2.8:range:' . $range;
      $ttl = now()->addMinutes(15);

      $ids = Cache::remember($key, $ttl, function() use($range) {
        $days = $range == 1 ? 2 : 31;
        $min_id = SnowflakeService::byDate(now()->subDays($days));
        return Status::select(
            'id', 
            'scope', 
            'type', 
            'is_nsfw', 
            'likes_count', 
            'created_at'
          )
          ->where('id', '>', $min_id)
          ->whereNull('uri')
          ->whereScope('public')
          ->whereIn('type', [
            'photo', 
            'photo:album', 
            'video'
          ])
          ->whereIsNsfw(false)
          ->orderBy('likes_count','desc')
          ->take(15)
          ->pluck('id');
      });

      $res = $ids->map(function($s) {
        return StatusService::get($s);
      });

      return response()->json($res);
    }

    public function trendingHashtags(Request $request)
    {
      return [];
    }

    public function trendingPlaces(Request $request)
    {
      return [];
    }
}
