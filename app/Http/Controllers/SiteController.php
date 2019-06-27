<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App, Auth, Cache, View;
use App\Util\Lexer\PrettyNumber;
use App\{Follower, Page, Profile, Status, User, UserFilter};
use App\Util\Localization\Localization;

class SiteController extends Controller
{
    public function home()
    {
        if (Auth::check()) {
            return $this->homeTimeline();
        } else {
            return $this->homeGuest();
        }
    }

    public function homeGuest()
    {
        return view('site.index');
    }

    public function homeTimeline()
    {
        return view('timeline.home');
    }

    public function changeLocale(Request $request, $locale)
    {
        // todo: add other locales after pushing new l10n strings
        $locales = Localization::languages();
        if(in_array($locale, $locales)) {
          session()->put('locale', $locale);
        }

        return redirect(route('site.language'));
    }

    public function about()
    {
        return Cache::remember('site:about', now()->addHours(12), function() {
            $page = Page::whereSlug('/site/about')->whereActive(true)->first();
            $stats = [
                'posts' => Status::whereLocal(true)->count(),
                'users' => User::whereNull('status')->count(),
                'admin' => User::whereIsAdmin(true)->first()
            ];
            if($page) {
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
        return Cache::remember('site:help:community-guidelines', now()->addDays(120), function() {
            $slug = '/site/kb/community-guidelines';
            $page = Page::whereSlug($slug)->whereActive(true)->first();
            return View::make('site.help.community-guidelines')->with(compact('page'))->render();
        });
    }

    public function privacy(Request $request)
    {
        return Cache::remember('site:privacy', now()->addDays(120), function() {
            $slug = '/site/privacy';
            $page = Page::whereSlug($slug)->whereActive(true)->first();
            return View::make('site.privacy')->with(compact('page'))->render();
        });
    }

    public function terms(Request $request)
    {
        return Cache::remember('site:terms', now()->addDays(120), function() {
            $slug = '/site/terms';
            $page = Page::whereSlug($slug)->whereActive(true)->first();
            return View::make('site.terms')->with(compact('page'))->render();
        });
    }
}
