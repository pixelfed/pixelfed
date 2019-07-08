<?php

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
use App\Transformer\Api\StatusStatelessTransformer;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use App\Services\StatusHashtagService;

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
        abort_if(!Auth::check(), 403);
        return view('discover.home');
    }

    public function showTags(Request $request, $hashtag)
    {
        abort_if(!config('instance.discover.tags.is_public') && !Auth::check(), 403);

        $tag = Hashtag::whereSlug($hashtag)->firstOrFail();
        $tagCount = StatusHashtagService::count($tag->id);
        return view('discover.tags.show', compact('tag', 'tagCount'));
    }

    public function showCategory(Request $request, $slug)
    {
      abort_if(!Auth::check(), 403);

      $tag = DiscoverCategory::whereActive(true)
        ->whereSlug($slug)
        ->firstOrFail();

      $posts = Cache::remember('discover:category-'.$tag->id.':posts', now()->addMinutes(15), function() use ($tag) {
          $tagids = $tag->hashtags->pluck('id')->toArray();
          $sids = StatusHashtag::whereIn('hashtag_id', $tagids)->orderByDesc('status_id')->take(500)->pluck('status_id')->toArray();
          $posts = Status::whereScope('public')->whereIn('id', $sids)->whereNull('uri')->whereType('photo')->whereNull('in_reply_to_id')->whereNull('reblog_of_id')->orderByDesc('created_at')->take(39)->get();
          return $posts;
      });
      $tag->posts_count = Cache::remember('discover:category-'.$tag->id.':posts_count', now()->addMinutes(30), function() use ($tag) {
        return $tag->posts()->whereScope('public')->count();
      });
      return view('discover.tags.category', compact('tag', 'posts'));
    }

    public function showPersonal(Request $request)
    {
      abort_if(!Auth::check(), 403);

      $profile = Auth::user()->profile;

      $tags = Cache::remember('profile-'.$profile->id.':hashtags', now()->addMinutes(15), function() use ($profile){
          return $profile->hashtags()->groupBy('hashtag_id')->inRandomOrder()->take(8)->get();
      });
      $following = Cache::remember('profile:following:'.$profile->id, now()->addMinutes(60), function() use ($profile) {
          $res = Follower::whereProfileId($profile->id)->pluck('following_id');
          return $res->push($profile->id)->toArray();
      });
      $posts = Cache::remember('profile-'.$profile->id.':hashtag-posts', now()->addMinutes(5), function() use ($profile, $following) {
          $posts = Status::whereScope('public')->withCount(['likes','comments'])->whereNotIn('profile_id', $following)->whereHas('media')->whereType('photo')->orderByDesc('created_at')->take(39)->get();
          $posts->post_count = Status::whereScope('public')->whereNotIn('profile_id', $following)->whereHas('media')->whereType('photo')->count();
          return $posts;
      });
      return view('discover.personal', compact('posts', 'tags'));
    }

    public function showLoops(Request $request)
    {
      if(config('exp.loops') != true) {
        return redirect('/');
      }
      return view('discover.loops.home');
    }

    public function loopsApi(Request $request)
    {
        abort_if(!config('exp.loops'), 403);
        
        // todo proper pagination, maybe LoopService
        $res = Cache::remember('discover:loops:recent', now()->addHours(1), function() {
          $loops = Status::whereType('video')
                  ->whereScope('public')
                  ->latest()
                  ->take(18)
                  ->get();

          $resource = new Fractal\Resource\Collection($loops, new StatusStatelessTransformer());
          return $this->fractal->createData($resource)->toArray();
        });
        return $res;
    }

    public function loopWatch(Request $request)
    {
        abort_if(!Auth::check(), 403);
        abort_if(!config('exp.loops'), 403);

        $this->validate($request, [
            'id' => 'integer|min:1'
        ]);
        $id = $request->input('id');

        // todo log loops

        return response()->json(200);
    }

    public function getHashtags(Request $request)
    {
      $auth = Auth::check();
      abort_if(!config('instance.discover.tags.is_public') && !$auth, 403);

      $this->validate($request, [
        'hashtag' => 'required|alphanum|min:2|max:124',
        'page' => 'nullable|integer|min:1|max:' . ($auth ? 19 : 3)
      ]);

      $page = $request->input('page') ?? '1';
      $end = $page > 1 ? $page * 9 : 0;
      $tag = $request->input('hashtag');

      $hashtag = Hashtag::whereName($tag)->firstOrFail();
      $res['tags'] = StatusHashtagService::get($hashtag->id, $page, $end);
      if($page == 1) {
        $res['follows'] = HashtagFollow::whereUserId(Auth::id())->whereHashtagId($hashtag->id)->exists();
      }
      return $res;
    }
}
