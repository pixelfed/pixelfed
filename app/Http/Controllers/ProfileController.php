<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Cache;
use DB;
use View;
use App\Follower;
use App\FollowRequest;
use App\Profile;
use App\Story;
use App\User;
use App\UserFilter;
use League\Fractal;
use App\Services\AccountService;
use App\Services\FollowerService;
use App\Services\StatusService;
use App\Util\Lexer\Nickname;
use App\Util\Webfinger\Webfinger;
use App\Transformer\ActivityPub\ProfileOutbox;
use App\Transformer\ActivityPub\ProfileTransformer;

class ProfileController extends Controller
{
	public function show(Request $request, $username)
	{
		// redirect authed users to Metro 2.0
		if($request->user()) {
			// unless they force static view
			if(!$request->has('fs') || $request->input('fs') != '1') {
				$pid = AccountService::usernameToId($username);
				if($pid) {
					return redirect('/i/web/profile/' . $pid);
				}
			}
		}

		$user = Profile::whereNull('domain')
			->whereNull('status')
			->whereUsername($username)
			->firstOrFail();

		if($request->wantsJson() && config_cache('federation.activitypub.enabled')) {
			return $this->showActivityPub($request, $user);
		}
		return $this->buildProfile($request, $user);
	}

	protected function buildProfile(Request $request, $user)
	{
		$username = $user->username;
		$loggedIn = Auth::check();
		$isPrivate = false;
		$isBlocked = false;
		if(!$loggedIn) {
			$key = 'profile:settings:' . $user->id;
			$ttl = now()->addHours(6);
			$settings = Cache::remember($key, $ttl, function() use($user) {
				return $user->user->settings;
			});

			if ($user->is_private == true) {
				$profile = null;
				return view('profile.private', compact('user'));
			}

			$owner = false;
			$is_following = false;

			$profile = $user;
			$settings = [
				'crawlable' => $settings->crawlable,
				'following' => [
					'count' => $settings->show_profile_following_count,
					'list' => $settings->show_profile_following
				],
				'followers' => [
					'count' => $settings->show_profile_follower_count,
					'list' => $settings->show_profile_followers
				]
			];
			return view('profile.show', compact('profile', 'settings'));
		} else {
			$key = 'profile:settings:' . $user->id;
			$ttl = now()->addHours(6);
			$settings = Cache::remember($key, $ttl, function() use($user) {
				return $user->user->settings;
			});

			if ($user->is_private == true) {
				$isPrivate = $this->privateProfileCheck($user, $loggedIn);
			}

			$isBlocked = $this->blockedProfileCheck($user);

			$owner = $loggedIn && Auth::id() === $user->user_id;
			$is_following = ($owner == false && Auth::check()) ? $user->followedBy(Auth::user()->profile) : false;

			if ($isPrivate == true || $isBlocked == true) {
				$requested = Auth::check() ? FollowRequest::whereFollowerId(Auth::user()->profile_id)
					->whereFollowingId($user->id)
					->exists() : false;
				return view('profile.private', compact('user', 'is_following', 'requested'));
			}

			$is_admin = is_null($user->domain) ? $user->user->is_admin : false;
			$profile = $user;
			$settings = [
				'crawlable' => $settings->crawlable,
				'following' => [
					'count' => $settings->show_profile_following_count,
					'list' => $settings->show_profile_following
				],
				'followers' => [
					'count' => $settings->show_profile_follower_count,
					'list' => $settings->show_profile_followers
				]
			];
			return view('profile.show', compact('profile', 'settings'));
		}
	}

	public function permalinkRedirect(Request $request, $username)
	{
		$user = Profile::whereNull('domain')->whereUsername($username)->firstOrFail();

		if ($request->wantsJson() && config_cache('federation.activitypub.enabled')) {
			return $this->showActivityPub($request, $user);
		}

		return redirect($user->url());
	}

	protected function privateProfileCheck(Profile $profile, $loggedIn)
	{
		if (!Auth::check()) {
			return true;
		}

		$user = Auth::user()->profile;
		if($user->id == $profile->id || !$profile->is_private) {
			return false;
		}

		$follows = Follower::whereProfileId($user->id)->whereFollowingId($profile->id)->exists();
		if ($follows == false) {
			return true;
		}

		return false;
	}

	public static function accountCheck(Profile $profile)
	{
		switch ($profile->status) {
			case 'disabled':
			case 'suspended':
			case 'delete':
				return view('profile.disabled');
				break;

			default:
				break;
		}
		return abort(404);
	}

	protected function blockedProfileCheck(Profile $profile)
	{
		$pid = Auth::user()->profile->id;
		$blocks = UserFilter::whereUserId($profile->id)
				->whereFilterType('block')
				->whereFilterableType('App\Profile')
				->pluck('filterable_id')
				->toArray();
		if (in_array($pid, $blocks)) {
			return true;
		}

		return false;
	}

	public function showActivityPub(Request $request, $user)
	{
		abort_if(!config_cache('federation.activitypub.enabled'), 404);
		abort_if($user->domain, 404);

		return Cache::remember('pf:activitypub:user-object:by-id:' . $user->id, 3600, function() use($user) {
			$fractal = new Fractal\Manager();
			$resource = new Fractal\Resource\Item($user, new ProfileTransformer);
			$res = $fractal->createData($resource)->toArray();
			return response(json_encode($res['data']))->header('Content-Type', 'application/activity+json');
		});
	}

	public function showAtomFeed(Request $request, $user)
	{
		abort_if(!config('federation.atom.enabled'), 404);

		$pid = AccountService::usernameToId($user);

		abort_if(!$pid, 404);

		$profile = AccountService::get($pid, true);

		abort_if(!$profile || $profile['locked'] || !$profile['local'], 404);

		$data = Cache::remember('pf:atom:user-feed:by-id:' . $profile['id'], 86400, function() use($pid, $profile) {
			$items = DB::table('statuses')
				->whereProfileId($pid)
				->whereVisibility('public')
				->whereType('photo')
				->orderByDesc('id')
				->take(10)
				->get()
				->map(function($status) {
					return StatusService::get($status->id);
				})
				->filter(function($status) {
					return $status &&
						isset($status['account']) &&
						isset($status['media_attachments']) &&
						count($status['media_attachments']);
				})
				->values();
			$permalink = config('app.url') . "/users/{$profile['username']}.atom";
			$headers = ['Content-Type' => 'application/atom+xml'];

			if($items && $items->count()) {
				$headers['Last-Modified'] = now()->parse($items->first()['created_at'])->toRfc7231String();
			}

			return compact('items', 'permalink', 'headers');
		});
		abort_if(!$data, 404);
		return response()
			->view('atom.user',
				[
					'profile' => $profile,
					'items' => $data['items'],
					'permalink' => $data['permalink']
				]
			)
			->withHeaders($data['headers']);
	}

	public function meRedirect()
	{
		abort_if(!Auth::check(), 404);
		return redirect(Auth::user()->url());
	}

	public function embed(Request $request, $username)
	{
		$res = view('profile.embed-removed');

		if(!config('instance.embed.profile')) {
			return response($res)->withHeaders(['X-Frame-Options' => 'ALLOWALL']);
		}

		if(strlen($username) > 15 || strlen($username) < 2) {
			return response($res)->withHeaders(['X-Frame-Options' => 'ALLOWALL']);
		}

		$profile = Profile::whereUsername($username)
			->whereIsPrivate(false)
			->whereNull('status')
			->whereNull('domain')
			->first();

		if(!$profile) {
			return response($res)->withHeaders(['X-Frame-Options' => 'ALLOWALL']);
		}

		if(AccountService::canEmbed($profile->user_id) == false) {
			return response($res)->withHeaders(['X-Frame-Options' => 'ALLOWALL']);
		}

		$profile = AccountService::get($profile->id);
		$res = view('profile.embed', compact('profile'));
		return response($res)->withHeaders(['X-Frame-Options' => 'ALLOWALL']);
	}

	public function stories(Request $request, $username)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);
		$profile = Profile::whereNull('domain')->whereUsername($username)->firstOrFail();
		$pid = $profile->id;
		$authed = Auth::user()->profile_id;
		abort_if($pid != $authed && !FollowerService::follows($authed, $pid), 404);
		$exists = Story::whereProfileId($pid)
			->whereActive(true)
			->exists();
		abort_unless($exists, 404);
		return view('profile.story', compact('pid', 'profile'));
	}
}
