<?php

namespace App\Http\Controllers;

use App;
use App\Follower;
use App\Profile;
use App\Status;
use App\User;
use App\UserFilter;
use App\Util\Lexer\PrettyNumber;
use Auth;
use Cache;
use Illuminate\Http\Request;

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
        $stats = Cache::remember('site:about:stats', 1440, function() {
            return [
                'posts' => Status::whereLocal(true)->count(),
                'users' => User::count(),
                'admin' => User::whereIsAdmin(true)->first()
            ];
        });
        return view('site.about', compact('stats'));
    }

    public function language()
    {
      return view('site.language');
    }
}
