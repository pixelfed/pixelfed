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
use App\Services\SnowflakeService;
use App\Services\StatusService;
use App\Services\UserFilterService;

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
				'range' => 'nullable|string|in:daily,monthly,yearly',
			]);

			$range = $request->input('range');
			$days = $range == 'monthly' ? 31 : ($range == 'daily' ? 1 : 365);
			$ttls = [
				1 => 1500,
				31 => 14400,
				365 => 86400
			];
			$key = ':api:discover:trending:v2.12:range:' . $days;

			$ids = Cache::remember($key, $ttls[$days], function() use($days) {
				$min_id = SnowflakeService::byDate(now()->subDays($days));
				return DB::table('statuses')
					->select(
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
					->take(30)
					->pluck('id');
			});

			$filtered = Auth::check() ? UserFilterService::filters(Auth::user()->profile_id) : [];

			$res = $ids->map(function($s) {
				return StatusService::get($s);
			})->filter(function($s) use($filtered) {
				return
					$s &&
					!in_array($s['account']['id'], $filtered) &&
					isset($s['account']);
			})->values();

			return response()->json($res);
		}

		public function trendingHashtags(Request $request)
		{
			$res = StatusHashtag::select('hashtag_id', \DB::raw('count(*) as total'))
				->groupBy('hashtag_id')
				->orderBy('total','desc')
				->where('created_at', '>', now()->subDays(90))
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
			return [];
		}
}
