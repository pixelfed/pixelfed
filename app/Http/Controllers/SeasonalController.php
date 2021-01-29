<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\AccountLog;
use App\Follower;
use App\Like;
use App\Status;
use App\StatusHashtag;
use Illuminate\Support\Facades\Cache;

class SeasonalController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth');
	}

	public function yearInReview()
	{
		abort_if(now()->gt('2021-03-01 00:00:00'), 404);
		abort_if(config('database.default') != 'mysql', 404);

		$profile = Auth::user()->profile;
		return view('account.yir', compact('profile'));
	}

	public function getData(Request $request)
	{
		abort_if(now()->gt('2021-03-01 00:00:00'), 404);
		abort_if(config('database.default') != 'mysql', 404);

		$uid = $request->user()->id;
		$pid = $request->user()->profile_id;
		$epoch = '2020-01-01 00:00:00';
		$epochStart = '2020-01-01 00:00:00';
		$epochEnd = '2020-12-31 23:59:59';

		$siteKey = 'seasonal:my2020:shared';
		$siteTtl = now()->addMonths(3);
		$userKey = 'seasonal:my2020:user:' . $uid;
		$userTtl = now()->addMonths(3);

		$shared = Cache::remember($siteKey, $siteTtl, function() use($epochStart, $epochEnd) {
			return [
				'average' => [
					'posts' => round(Status::selectRaw('*, count(profile_id) as count')
					->whereNull('uri')
					->whereIn('type', ['photo','photo:album','video','video:album','photo:video:album'])
					->where('created_at', '>', $epochStart)
					->where('created_at', '<', $epochEnd)
					->groupBy('profile_id')
					->pluck('count')
					->avg()),

					'likes' => round(Like::selectRaw('*, count(profile_id) as count')
					->where('created_at', '>', $epochStart)
					->where('created_at', '<', $epochEnd)
					->groupBy('profile_id')
					->pluck('count')
					->avg()),
				],

				'popular' => [

					'hashtag' => StatusHashtag::selectRaw('*,count(hashtag_id) as count')
						->where('created_at', '>', $epochStart)
						->where('created_at', '<', $epochEnd)
						->groupBy('hashtag_id')
						->orderByDesc('count')
						->take(1)
						->get()
						->map(function($sh) {
							return [
								'name' => $sh->hashtag->name,
								'count' => $sh->count
							];
						})
						->first(),

						'post' => Status::whereScope('public')
						->where('likes_count', '>', 1)
						->whereIsNsfw(false)
						->where('created_at', '>', $epochStart)
						->where('created_at', '<', $epochEnd)
						->orderByDesc('likes_count')
						->take(1)
						->get()
						->map(function($status) {
							return [
								'id' => (string) $status->id,
								'username' => (string) $status->profile->username,
								'created_at' => $status->created_at->format('M d, Y'),
								'type' => $status->type,
								'url' => $status->url(),
								'thumb' => $status->thumb(),
								'likes_count' => $status->likes_count,
								'reblogs_count' => $status->reblogs_count,
								'reply_count' => $status->reply_count ?? 0,
							];
						})
						->first(),

						'places' => Status::selectRaw('*, count(place_id) as count')
						->whereNotNull('place_id')
						->having('count', '>', 1)
						->where('created_at', '>', $epochStart)
						->where('created_at', '<', $epochEnd)
						->groupBy('place_id')
						->orderByDesc('count')
						->take(1)
						->get()
						->map(function($sh) {
							return [
								'name' => $sh->place->getName(),
								'url' => $sh->place->url(),
								'count' => $sh->count
							];
						})
					->first()
				],

			];
		});

		$res = Cache::remember($userKey, $userTtl, function() use($uid, $pid, $epochStart, $epochEnd, $request) {
			return [
				'account' => [
					'user_id' => $request->user()->id,
					'created_at' => $request->user()->created_at->format('M d, Y'),
					'created_this_year' => $request->user()->created_at->gt('2020-01-01 00:00:00'),
					'created_months_ago' => $request->user()->created_at->diffInMonths(now()),
					'followers_this_year' => Follower::whereFollowingId($pid)
						->where('created_at', '>', $epochStart)
						->where('created_at', '<', $epochEnd)
						->count(),
					'followed_this_year' => Follower::whereProfileId($pid)
						->where('created_at', '>', $epochStart)
						->where('created_at', '<', $epochEnd)
						->count(),
					'most_popular' => Status::whereProfileId($pid)
						->where('likes_count', '>', 1)
						->where('created_at', '>', $epochStart)
						->where('created_at', '<', $epochEnd)
						->orderByDesc('likes_count')
						->take(1)
						->get()
						->map(function($status) {
							return [
								'id' => (string) $status->id,
								'username' => (string) $status->profile->username,
								'created_at' => $status->created_at->format('M d, Y'),
								'type' => $status->type,
								'url' => $status->url(),
								'thumb' => $status->thumb(),
								'likes_count' => $status->likes_count,
								'reblogs_count' => $status->reblogs_count,
								'reply_count' => $status->reply_count ?? 0,
							];
						})
					->first(),
					'posts_count' => Status::whereProfileId($pid)
						->whereIn('type', ['photo','photo:album','video','video:album','photo:video:album'])
						->where('created_at', '>', $epochStart)
						->where('created_at', '<', $epochEnd)
						->count(),
					'likes_count' => Like::whereProfileId($pid)
						->where('created_at', '>', $epochStart)
						->where('created_at', '<', $epochEnd)
						->count(),
					'hashtag' => StatusHashtag::selectRaw('*, count(hashtag_id) as count')
						->whereProfileId($pid)
						->where('created_at', '>', $epochStart)
						->where('created_at', '<', $epochEnd)
						->groupBy('profile_id')
						->orderByDesc('count')
						->take(1)
						->get()
						->map(function($sh) {
							return [
								'name' => $sh->hashtag->name,
								'count' => $sh->count
							];
						})
					->first(),
					'places' => Status::selectRaw('*, count(place_id) as count')
						->whereNotNull('place_id')
						->having('count', '>', 1)
						->whereProfileId($pid)
						->where('created_at', '>', $epochStart)
						->where('created_at', '<', $epochEnd)
						->groupBy('place_id')
						->orderByDesc('count')
						->take(1)
						->get()
						->map(function($sh) {
							return [
								'name' => $sh->place->getName(),
								'url' => $sh->place->url(),
								'count' => $sh->count
							];
						})
					->first(),
					'places_total' => Status::whereProfileId($pid)
						->where('created_at', '>', $epochStart)
						->where('created_at', '<', $epochEnd)
						->whereNotNull('place_id')
						->count()
				]
			];
		});

		return response()->json(array_merge($res, $shared));
	}

	public function store(Request $request)
	{
		abort_if(now()->gt('2021-03-01 00:00:00'), 404);
		abort_if(config('database.default') != 'mysql', 404);
		$this->validate($request, [
			'profile_id' => 'required',
			'type' => 'required|string|in:view,hide'
		]);

		$user = $request->user();

		$log = new AccountLog();
		$log->user_id = $user->id;
		$log->item_type = 'App\User';
		$log->item_id = $user->id;
		$log->action = $request->input('type') == 'view' ? 'seasonal.my2020.view' : 'seasonal.my2020.hide';
		$log->ip_address = $request->ip();
		$log->user_agent = $request->user_agent();
		$log->save();
	}
}
