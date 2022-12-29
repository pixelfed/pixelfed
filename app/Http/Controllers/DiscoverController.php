<?php

namespace App\Http\Controllers;

use App\{
	DiscoverCategory,
	Follower,
	Hashtag,
	HashtagFollow,
	Instance,
	Like,
	Profile,
	Status,
	StatusHashtag,
	UserFilter
};
use Auth, DB, Cache;
use Illuminate\Http\Request;
use App\Services\BookmarkService;
use App\Services\ConfigCacheService;
use App\Services\HashtagService;
use App\Services\LikeService;
use App\Services\ReblogService;
use App\Services\StatusHashtagService;
use App\Services\SnowflakeService;
use App\Services\StatusService;
use App\Services\TrendingHashtagService;
use App\Services\UserFilterService;

class DiscoverController extends Controller
{
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

	public function getHashtags(Request $request)
	{
		$user = $request->user();
		abort_if(!config('instance.discover.tags.is_public') && !$user, 403);

		$this->validate($request, [
			'hashtag' => 'required|string|min:1|max:124',
			'page' => 'nullable|integer|min:1|max:' . ($user ? 29 : 10)
		]);

		$page = $request->input('page') ?? '1';
		$end = $page > 1 ? $page * 9 : 0;
		$tag = $request->input('hashtag');

		$hashtag = Hashtag::whereName($tag)->firstOrFail();
		if($user) {
			$res['follows'] = HashtagService::isFollowing($user->profile_id, $hashtag->id);
		}
		$res['hashtag'] = [
			'name' => $hashtag->name,
			'url' => $hashtag->url()
		];
		if($user) {
			$tags = StatusHashtagService::get($hashtag->id, $page, $end);
			$res['tags'] = collect($tags)
				->map(function($tag) use($user) {
					$tag['status']['favourited'] = (bool) LikeService::liked($user->profile_id, $tag['status']['id']);
					$tag['status']['reblogged'] = (bool) ReblogService::get($user->profile_id, $tag['status']['id']);
					$tag['status']['bookmarked'] = (bool) BookmarkService::get($user->profile_id, $tag['status']['id']);
					return $tag;
				})
				->filter(function($tag) {
					if(!StatusService::get($tag['status']['id'])) {
						return false;
					}
					return true;
				})
				->values();
		} else {
			if($page != 1) {
				$res['tags'] = [];
				return $res;
			}
			$key = 'discover:tags:public_feed:' . $hashtag->id . ':page:' . $page;
			$tags = Cache::remember($key, 43200, function() use($hashtag, $page, $end) {
				return collect(StatusHashtagService::get($hashtag->id, $page, $end))
					->filter(function($tag) {
						if(!$tag['status']['local']) {
							return false;
						}
						return true;
					})
					->values();
			});
			$res['tags'] = collect($tags)
				->filter(function($tag) {
					if(!StatusService::get($tag['status']['id'])) {
						return false;
					}
					return true;
				})
				->values();
		}
		return $res;
	}

	public function profilesDirectory(Request $request)
	{
		return redirect('/')->with('statusRedirect', 'The Profile Directory is unavailable at this time.');
	}

	public function profilesDirectoryApi(Request $request)
	{
		return ['error' => 'Temporarily unavailable.'];
	}

	public function trendingApi(Request $request)
	{
		abort_if(config('instance.discover.public') == false && !$request->user(), 403);

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
		abort_if(!$request->user(), 403);

		$res = TrendingHashtagService::getTrending();
		return $res;
	}

	public function trendingPlaces(Request $request)
	{
		return [];
	}

	public function myMemories(Request $request)
	{
		abort_if(!$request->user(), 404);
		$pid = $request->user()->profile_id;
		abort_if(!$this->config()['memories']['enabled'], 404);
		$type = $request->input('type') ?? 'posts';

		switch($type) {
			case 'posts':
				$res = Status::whereProfileId($pid)
					->whereDay('created_at', date('d'))
					->whereMonth('created_at', date('m'))
					->whereYear('created_at', '!=', date('Y'))
					->whereNull(['reblog_of_id', 'in_reply_to_id'])
					->limit(20)
					->pluck('id')
					->map(function($id) {
						return StatusService::get($id, false);
					})
					->filter(function($post) {
						return $post && isset($post['account']);
					})
					->values();
			break;

			case 'liked':
				$res = Like::whereProfileId($pid)
					->whereDay('created_at', date('d'))
					->whereMonth('created_at', date('m'))
					->whereYear('created_at', '!=', date('Y'))
					->orderByDesc('status_id')
					->limit(20)
					->pluck('status_id')
					->map(function($id) {
						$status = StatusService::get($id, false);
						$status['favourited'] = true;
						return $status;
					})
					->filter(function($post) {
						return $post && isset($post['account']);
					})
					->values();
			break;
		}

		return $res;
	}

	public function accountInsightsPopularPosts(Request $request)
	{
		abort_if(!$request->user(), 404);
		$pid = $request->user()->profile_id;
		abort_if(!$this->config()['insights']['enabled'], 404);
		$posts = Cache::remember('pf:discover:metro2:accinsights:popular:' . $pid, 43200, function() use ($pid) {
			return Status::whereProfileId($pid)
			->whereNotNull('likes_count')
			->orderByDesc('likes_count')
			->limit(12)
			->pluck('id')
			->map(function($id) {
				return StatusService::get($id, false);
			})
			->filter(function($post) {
				return $post && isset($post['account']);
			})
			->values();
		});

		return $posts;
	}

	public function config()
	{
		$cc = ConfigCacheService::get('config.discover.features');
		if($cc) {
			return is_string($cc) ? json_decode($cc, true) : $cc;
		}
		return [
			'hashtags' => [
				'enabled' => false,
			],
			'memories' => [
				'enabled' => false,
			],
			'insights' => [
				'enabled' => false,
			],
			'friends' => [
				'enabled' => false,
			],
			'server' => [
				'enabled' => false,
				'mode' => 'allowlist',
				'domains' => []
			]
		];
	}

	public function serverTimeline(Request $request)
	{
		abort_if(!$request->user(), 404);
		abort_if(!$this->config()['server']['enabled'], 404);
		$pid = $request->user()->profile_id;
		$domain = $request->input('domain');
		$config = $this->config();
		$domains = explode(',', $config['server']['domains']);
		abort_unless(in_array($domain, $domains), 400);

		$res = Status::whereNotNull('uri')
			->where('uri', 'like', 'https://' . $domain . '%')
			->whereNull(['in_reply_to_id', 'reblog_of_id'])
			->orderByDesc('id')
			->limit(12)
			->pluck('id')
			->map(function($id) {
				return StatusService::get($id);
			})
			->filter(function($post) {
				return $post && isset($post['account']);
			})
			->values();
		return $res;
	}

	public function enabledFeatures(Request $request)
	{
		abort_if(!$request->user(), 404);
		return $this->config();
	}

	public function updateFeatures(Request $request)
	{
		abort_if(!$request->user(), 404);
		abort_if(!$request->user()->is_admin, 404);
		$pid = $request->user()->profile_id;
		$this->validate($request, [
			'features.friends.enabled' => 'boolean',
			'features.hashtags.enabled' => 'boolean',
			'features.insights.enabled' => 'boolean',
			'features.memories.enabled' => 'boolean',
			'features.server.enabled' => 'boolean',
		]);
		$res = $request->input('features');
		if($res['server'] && isset($res['server']['domains']) && !empty($res['server']['domains'])) {
			$parts = explode(',', $res['server']['domains']);
			$parts = array_filter($parts, function($v) {
				$len = strlen($v);
				$pos = strpos($v, '.');
				$domain = trim($v);
				if($pos == false || $pos == ($len + 1)) {
					return false;
				}
				if(!Instance::whereDomain($domain)->exists()) {
					return false;
				}
				return true;
			});
			$parts = array_slice($parts, 0, 10);
			$d = implode(',', array_map('trim', $parts));
			$res['server']['domains'] = $d;
		}
		ConfigCacheService::put('config.discover.features', json_encode($res));
		return $res;
	}
}
