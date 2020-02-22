<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use Auth;
use Cache;
use View;
use App\Util\Lexer\PrettyNumber;
use App\Follower;
use App\Page;
use App\Profile;
use App\Status;
use App\User;
use App\UserFilter;
use App\Util\Localization\Localization;
use App\Services\FollowerService;

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
        if (in_array($locale, $locales)) {
            if ($request->user()) {
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
        return Cache::remember('site:about', now()->addHours(12), function () {
            app()->setLocale('en');
            $page = Page::whereSlug('/site/about')->whereActive(true)->first();
            $stats = [
                'posts' => Status::whereLocal(true)->count(),
                'users' => User::whereNull('status')->count(),
                'admin' => User::whereIsAdmin(true)->first()
            ];
            if ($page) {
                return View::make('site.about-custom')->with(compact('page', 'stats'))->render();
            } else {
                return View::make('site.about')->with(compact('stats'))->render();
            }
        });
    }

    public function language()
    {
        return view('site.language');
    }

    public function communityGuidelines(Request $request)
    {
        return Cache::remember('site:help:community-guidelines', now()->addDays(120), function () {
            $slug = '/site/kb/community-guidelines';
            $page = Page::whereSlug($slug)->whereActive(true)->first();
            return View::make('site.help.community-guidelines')->with(compact('page'))->render();
        });
    }

    public function privacy(Request $request)
    {
        $page = Cache::remember('site:privacy', now()->addDays(120), function () {
            $slug = '/site/privacy';
            $page = Page::whereSlug($slug)->whereActive(true)->first();
        });
        return View::make('site.privacy')->with(compact('page'))->render();
    }

    public function terms(Request $request)
    {
        $page = Cache::remember('site:terms', now()->addDays(120), function () {
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
        $url = urldecode(request()->input('url'));
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
}
