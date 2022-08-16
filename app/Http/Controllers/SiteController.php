<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App, Auth, Cache, View;
use App\Util\Lexer\PrettyNumber;
use App\{Follower, Page, Profile, Status, User, UserFilter};
use App\Util\Localization\Localization;
use App\Services\FollowerService;
use App\Util\ActivityPub\Helpers;

class SiteController extends Controller
{
	public function home(Request $request)
	{
		if (Auth::check()) {
			return $this->homeTimeline($request);
		} else {
			return $this->homeGuest();
		}
	}

	public function homeGuest()
	{
		return view('site.index');
	}

	public function homeTimeline(Request $request)
	{
		if($request->has('force_old_ui')) {
			return view('timeline.home', ['layout' => 'feed']);
		}

		return redirect('/i/web');
	}

	public function changeLocale(Request $request, $locale)
	{
		// todo: add other locales after pushing new l10n strings
		$locales = Localization::languages();
		if(in_array($locale, $locales)) {
			if($request->user()) {
				$user = $request->user();
				$user->language = $locale;
				$user->save();
			}
		  session()->put('locale', $locale);
		}

		return redirect(route('site.language'));
	}

	public function about()
	{
		return Cache::remember('site.about_v2', now()->addMinutes(15), function() {
			$user_count = number_format(User::count());
			$post_count = number_format(Status::count());
			$rules = config_cache('app.rules') ? json_decode(config_cache('app.rules'), true) : null;
			return view('site.about', compact('rules', 'user_count', 'post_count'))->render();
		});
	}

	public function language()
	{
	  return view('site.language');
	}

	public function communityGuidelines(Request $request)
	{
		return Cache::remember('site:help:community-guidelines', now()->addDays(120), function() {
			$slug = '/site/kb/community-guidelines';
			$page = Page::whereSlug($slug)->whereActive(true)->first();
			return View::make('site.help.community-guidelines')->with(compact('page'))->render();
		});
	}

	public function privacy(Request $request)
	{
		$page = Cache::remember('site:privacy', now()->addDays(120), function() {
			$slug = '/site/privacy';
			return Page::whereSlug($slug)->whereActive(true)->first();
		});
		return View::make('site.privacy')->with(compact('page'))->render();
	}

	public function terms(Request $request)
	{
		$page = Cache::remember('site:terms', now()->addDays(120), function() {
			$slug = '/site/terms';
			return Page::whereSlug($slug)->whereActive(true)->first();
		});
		return View::make('site.terms')->with(compact('page'))->render();
	}

	public function redirectUrl(Request $request)
	{
		abort_if(!$request->user(), 404);
		$this->validate($request, [
			'url' => 'required|url'
		]);
		$url = request()->input('url');
		abort_if(Helpers::validateUrl($url) == false, 404);
		return view('site.redirect', compact('url'));
	}

	public function followIntent(Request $request)
	{
		$this->validate($request, [
			'user' => 'string|min:1|max:15|exists:users,username',
		]);
		$profile = Profile::whereUsername($request->input('user'))->firstOrFail();
		$user = $request->user();
		abort_if($user && $profile->id == $user->profile_id, 404);
		$following = $user != null ? FollowerService::follows($user->profile_id, $profile->id) : false;
		return view('site.intents.follow', compact('profile', 'user', 'following'));
	}

	public function legacyProfileRedirect(Request $request, $username)
	{
		$username = Str::contains($username, '@') ? '@' . $username : $username;
		if(str_contains($username, '@')) {
			$profile = Profile::whereUsername($username)
				->firstOrFail();

			if($profile->domain == null) {
				$url = "/$profile->username";
			} else {
				$url = "/i/web/profile/_/{$profile->id}";
			}

		} else {
			$profile = Profile::whereUsername($username)
				->whereNull('domain')
				->firstOrFail();
			$url = "/$profile->username";
		}

		return redirect($url);
	}

	public function legacyWebfingerRedirect(Request $request, $username, $domain)
	{
		$un = '@'.$username.'@'.$domain;
		$profile = Profile::whereUsername($un)
			->firstOrFail();

		if($profile->domain == null) {
			$url = "/$profile->username";
		} else {
			$url = $request->user() ? "/i/web/profile/_/{$profile->id}" : $profile->url();
		}

		return redirect($url);
	}

	public function legalNotice(Request $request)
	{
		$page = Cache::remember('site:legal-notice', now()->addDays(120), function() {
			$slug = '/site/legal-notice';
			return Page::whereSlug($slug)->whereActive(true)->first();
		});
		abort_if(!$page, 404);
		return View::make('site.legal-notice')->with(compact('page'))->render();
	}
}
