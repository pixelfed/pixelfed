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
        $data = Cache::remember('site:landing:data', now()->addHours(3), function() {
            return [
                'stats' => [
                    'posts' => App\Util\Lexer\PrettyNumber::convert(App\Status::count()),
                    'likes' => App\Util\Lexer\PrettyNumber::convert(App\Like::count()),
                    'hashtags' => App\Util\Lexer\PrettyNumber::convert(App\StatusHashtag::count())
                ],
            ];
        });
        return view('site.index', compact('data'));
    }

    public function homeTimeline(Request $request)
    {
        $this->validate($request, [
            'layout' => 'nullable|string|in:grid,feed'
        ]);
        $layout = $request->input('layout', 'feed');
        return view('timeline.home', compact('layout'));
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
        $page = Page::whereSlug('/site/about')->whereActive(true)->first();
        $stats = Cache::remember('site:about:stats-v1', now()->addHours(12), function() {
            return [
                'posts' => Status::count(),
                'users' => User::whereNull('status')->count(),
                'admin' => User::whereIsAdmin(true)->first()
            ];
        });
        $path = $page ? 'site.about-custom' : 'site.about';
        return view($path, compact('page', 'stats'));
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
            $page = Page::whereSlug($slug)->whereActive(true)->first();
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
}
