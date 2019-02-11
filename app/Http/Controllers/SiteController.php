<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App, Auth, Cache, View;
use App\Util\Lexer\PrettyNumber;
use App\{Follower, Page, Profile, Status, User, UserFilter};

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
        $locales = ['en'];
        if(in_array($locale, $locales)) {
          session()->put('locale', $locale);
        }

        return redirect()->back();
    }

    public function about()
    {
        $res = Cache::remember('site:about', 120, function() {
            $custom = Page::whereSlug('/site/about')->whereActive(true)->exists();
            if($custom) {
              $stats = Cache::remember('site:about:stats', 60, function() {
                    return [
                        'posts' => Status::whereLocal(true)->count(),
                        'users' => User::count(),
                        'admin' => User::whereIsAdmin(true)->first()
                    ];
                });
                return View::make('site.about')->with('stats', $stats)->render();
            } else {
                $stats = Cache::remember('site:about:stats', 60, function() {
                    return [
                        'posts' => Status::whereLocal(true)->count(),
                        'users' => User::count(),
                        'admin' => User::whereIsAdmin(true)->first()
                    ];
                });
                //return view('site.about', compact('stats'));
                return View::make('site.about')->with('stats', $stats)->render();
            }
        });
        return $res;
    }

    public function language()
    {
      return view('site.language');
    }

    public function communityGuidelines(Request $request)
    {
        $slug = '/site/kb/community-guidelines';
        $page = Page::whereSlug($slug)->whereActive(true)->first();
        return view('site.help.community-guidelines', compact('page'));
    }

}
