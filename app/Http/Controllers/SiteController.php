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
        $pid = Auth::user()->profile->id;
        // TODO: Use redis for timelines
        $following = Follower::whereProfileId(Auth::user()->profile->id)->pluck('following_id');
        $following->push(Auth::user()->profile->id);
        $filtered = UserFilter::whereUserId($pid)
                  ->whereFilterableType('App\Profile')
                  ->whereIn('filter_type', ['mute', 'block'])
                  ->pluck('filterable_id');
        $timeline = Status::whereIn('profile_id', $following)
                  ->whereNotIn('profile_id', $filtered)
                  ->whereHas('media')
                  ->whereVisibility('public')
                  ->orderBy('id', 'desc')
                  ->withCount(['comments', 'likes', 'shares'])
                  ->simplePaginate(20);
        $type = 'personal';

        return view('timeline.template', compact('timeline', 'type'));
    }

    public function changeLocale(Request $request, $locale)
    {
        if (!App::isLocale($locale)) {
            return redirect()->back();
        }
        App::setLocale($locale);

        return redirect()->back();
    }

    public function about()
    {
        $res = Cache::remember('site:page:about', 15, function () {
            $statuses = Status::whereHas('media')
              ->whereNull('in_reply_to_id')
              ->whereNull('reblog_of_id')
              ->count();
            $statusCount = PrettyNumber::convert($statuses);
            $userCount = PrettyNumber::convert(User::count());
            $remoteCount = PrettyNumber::convert(Profile::whereNotNull('remote_url')->count());
            $adminContact = User::whereIsAdmin(true)->first();

            return view('site.about')->with(compact('statusCount', 'userCount', 'remoteCount', 'adminContact'))->render();
        });

        return $res;
    }
}
