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
use App\Transformer\Api\AccountTransformer;
use App\Transformer\Api\AccountWithStatusesTransformer;
use App\Transformer\Api\StatusTransformer;
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

        $tag = Hashtag::whereName($hashtag)
          ->orWhere('slug', $hashtag)
          ->firstOrFail();
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
        $res = Cache::remember('discover:loops:recent', now()->addHours(6), function() {
          $loops = Status::whereType('video')
                  ->whereNull('uri')
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
        'hashtag' => 'required|string|min:1|max:124',
        'page' => 'nullable|integer|min:1|max:' . ($auth ? 29 : 10)
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

    public function profilesDirectory(Request $request)
    {
      return redirect('/')->with('statusRedirect', 'The Profile Directory is unavailable at this time.');
      return view('discover.profiles.home');
    }

    public function profilesDirectoryApi(Request $request)
    {
      $this->validate($request, [
        'page' => 'integer|max:10'
      ]);

      return ['error' => 'Temporarily unavailable.'];

      $page = $request->input('page') ?? 1;
      $key = 'discover:profiles:page:' . $page;
      $ttl = now()->addHours(12);

      $res = Cache::remember($key, $ttl, function() {
          $profiles = Profile::whereNull('domain')
                ->whereNull('status')
                ->whereIsPrivate(false)
                ->has('statuses')
                ->whereIsSuggestable(true)
                // ->inRandomOrder()
                ->simplePaginate(8);
          $resource = new Fractal\Resource\Collection($profiles, new AccountTransformer());
          return $this->fractal->createData($resource)->toArray();
      });

      return $res;
    }

    public function trendingApi(Request $request)
    {
      $this->validate($request, [
        'range' => 'nullable|string|in:daily,monthly,alltime'
      ]);

      $range = $request->filled('range') ? 
        $request->input('range') == 'alltime' ? '-1' : 
        ($request->input('range') == 'daily' ? 1 : 31) : 1;

      $key = ':api:discover:trending:v1:range:' . $range;
      $ttl = now()->addHours(2);
      $res = Cache::remember($key, $ttl, function() use($range) {
        if($range == '-1') {
          $res = Status::whereVisibility('public')
          ->orderBy('likes_count','desc')
          ->take(12)
          ->get();
        } else {
          $res = Status::whereVisibility('public')
          ->orderBy('likes_count','desc')
          ->take(12)
          ->where('created_at', '>', now()->subDays($range))
          ->get();
          }
        $resource = new Fractal\Resource\Collection($res, new StatusStatelessTransformer());
        return $this->fractal->createData($resource)->toArray();
      });
      return response()->json($res, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }

    public function trendingHashtags(Request $request)
    {
      $res = StatusHashtag::select('hashtag_id', \DB::raw('count(*) as total'))
        ->groupBy('hashtag_id')
        ->orderBy('total','desc')
        ->where('created_at', '>', now()->subDays(4))
        ->take(9)
        ->get()
        ->map(function($h) {
          $hashtag = $h->hashtag;
          return [
            'id' => $hashtag->id,
            'total' => $h->total,
            'name' => '#'.$hashtag->name,
            'url' => $hashtag->url('?src=dsh1')
          ];
        });
      return $res;
    }

    public function trendingPlaces(Request $request)
    {
      $res = Status::select('place_id',DB::raw('count(place_id) as total'))
        ->whereNotNull('place_id')
        ->where('created_at','>',now()->subDays(14))
        ->groupBy('place_id')
        ->orderBy('total')
        ->limit(4)
        ->get()
        ->map(function($s){
          $p = $s->place;
          return [
            'name' => $p->name,
            'country' => $p->country,
            'url' => $p->url()
          ];
        });

      return $res;
    }
}
