<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Cache;
use App\Follower;
use App\Profile;
use App\User;
use App\UserFilter;
use League\Fractal;
use App\Util\Lexer\Nickname;
use App\Util\Webfinger\Webfinger;
use App\Transformer\ActivityPub\ProfileOutbox;
use App\Transformer\ActivityPub\ProfileTransformer;

class ProfileController extends Controller
{
    public function show(Request $request, $username)
    {
        $user = Profile::whereUsername($username)->firstOrFail();
        return $this->buildProfile($request, $user);
    }

    // TODO: refactor this mess
    protected function buildProfile(Request $request, $user)
    {
        $username = $user->username;
        $loggedIn = Auth::check();
        $isPrivate = false;

        if ($user->remote_url) {
            $settings = new \StdClass;
            $settings->crawlable = false;
        } else {
            $settings = User::whereUsername($username)->firstOrFail()->settings;
        }

        if ($request->wantsJson() && config('pixelfed.activitypub_enabled')) {
            return $this->showActivityPub($request, $user);
        }

        if ($user->is_private == true) {
            $isPrivate = $this->privateProfileCheck($user);
        }

        if ($loggedIn == true) {
            $isPrivate = $this->blockedProfileCheck($user);
        }

        if ($isPrivate == true) {
            return view('profile.private', compact('user'));
        } else {
            $owner = $loggedIn && Auth::id() === $user->user_id;
            $is_following = ($owner == false && Auth::check()) ? $user->followedBy(Auth::user()->profile) : false;
            $is_admin = is_null($user->domain) ? $user->user->is_admin : false;
            $timeline = $user->statuses()
                  ->whereHas('media')
                  ->whereNull('in_reply_to_id')
                  ->whereNull('reblog_of_id')
                  ->orderBy('created_at', 'desc')
                  ->withCount(['comments', 'likes'])
                  ->simplePaginate(21);
        }

        return view('profile.show', compact('user', 'settings', 'owner', 'is_following', 'is_admin', 'timeline'));
    }

    public function permalinkRedirect(Request $request, $username)
    {
        $user = Profile::whereUsername($username)->firstOrFail();
        $settings = User::whereUsername($username)->firstOrFail()->settings;

        if ($request->wantsJson() && config('pixelfed.activitypub_enabled')) {
            return $this->showActivityPub($request, $user);
        }

        return redirect($user->url());
    }

    protected function privateProfileCheck(Profile $profile)
    {
        if (Auth::check() === false) {
            return false;
        }
        $pid = Auth::user()->profile->id;
        $follower_ids = $profile->followers()->pluck('followers.profile_id')->toArray();
        if (!in_array($pid, $follower_ids) && $pid !== $profile->id) {
            return false;
        }

        return true;
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
        $fractal = new Fractal\Manager();
        $resource = new Fractal\Resource\Item($user, new ProfileTransformer);
        $res = $fractal->createData($resource)->toArray();
        return response(json_encode($res['data']))->header('Content-Type', 'application/activity+json');
    }

    public function showAtomFeed(Request $request, $user)
    {
        $profile = Profile::whereUsername($user)->firstOrFail();
        $items = $profile->statuses()->orderBy('created_at', 'desc')->take(10)->get();
        return response()->view('atom.user', compact('profile', 'items'))
        ->header('Content-Type', 'application/atom+xml');
    }

    public function followers(Request $request, $username)
    {
        $profile = $user = Profile::whereUsername($username)->firstOrFail();
        // TODO: fix $profile/$user mismatch in profile & follower templates
        $owner = Auth::check() && Auth::id() === $user->user_id;
        $is_following = ($owner == false && Auth::check()) ? $user->followedBy(Auth::user()->profile) : false;
        $followers = $profile->followers()->orderBy('created_at', 'desc')->simplePaginate(12);
        $is_admin = is_null($user->domain) ? $user->user->is_admin : false;
        if ($user->remote_url) {
            $settings = new \StdClass;
            $settings->crawlable = false;
        } else {
            $settings = User::whereUsername($username)->firstOrFail()->settings;
        }
        return view('profile.followers', compact('user', 'profile', 'followers', 'owner', 'is_following', 'is_admin', 'settings'));
    }

    public function following(Request $request, $username)
    {
        $profile = Profile::whereUsername($username)->firstOrFail();
        // TODO: fix $profile/$user mismatch in profile & follower templates
        $user = $profile;
        $owner = Auth::check() && Auth::id() === $user->user_id;
        $is_following = ($owner == false && Auth::check()) ? $user->followedBy(Auth::user()->profile) : false;
        $following = $profile->following()->orderBy('created_at', 'desc')->simplePaginate(12);
        $is_admin = is_null($user->domain) ? $user->user->is_admin : false;
        if ($user->remote_url) {
            $settings = new \StdClass;
            $settings->crawlable = false;
        } else {
            $settings = User::whereUsername($username)->firstOrFail()->settings;
        }
        return view('profile.following', compact('user', 'profile', 'following', 'owner', 'is_following', 'is_admin', 'settings'));
    }

    public function savedBookmarks(Request $request, $username)
    {
        if (Auth::check() === false || $username !== Auth::user()->username) {
            abort(403);
        }
        $user = Auth::user()->profile;
        $settings = User::whereUsername($username)->firstOrFail()->settings;
        $owner = true;
        $following = false;
        $timeline = $user->bookmarks()->withCount(['likes','comments'])->orderBy('created_at', 'desc')->simplePaginate(10);
        $is_following = ($owner == false && Auth::check()) ? $user->followedBy(Auth::user()->profile) : false;
        $is_admin = is_null($user->domain) ? $user->user->is_admin : false;
        return view('profile.show', compact('user', 'settings', 'owner', 'following', 'timeline', 'is_following', 'is_admin'));
    }
}
