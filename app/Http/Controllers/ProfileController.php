<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth, Cache;
use App\{Follower, Profile, User};
use League\Fractal;
use App\Util\Lexer\Nickname;
use App\Util\Webfinger\Webfinger;
use App\Transformer\ActivityPub\{
  ProfileOutbox, 
  ProfileTransformer
};

class ProfileController extends Controller
{
    public function show(Request $request, $username)
    {
      $user = Profile::whereUsername($username)->firstOrFail();
      $settings = User::whereUsername($username)->firstOrFail()->settings;

      $mimes = [
        'application/activity+json', 
        'application/ld+json; profile="https://www.w3.org/ns/activitystreams"'
      ];

      if(in_array($request->header('accept'), $mimes) && config('pixelfed.activitypub_enabled')) {
        return $this->showActivityPub($request, $user);
      }
      if($user->is_private == true) {
        $can_access = $this->privateProfileCheck($user);
        if($can_access !== true) {
          abort(403);
        }
      }
      // TODO: refactor this mess
      $owner = Auth::check() && Auth::id() === $user->user_id;
      $is_following = ($owner == false && Auth::check()) ? $user->followedBy(Auth::user()->profile) : false;
      $is_admin = is_null($user->domain) ? $user->user->is_admin : false;
      $timeline = $user->statuses()
                  ->whereHas('media')
                  ->whereNull('in_reply_to_id')
                  ->orderBy('created_at','desc')
                  ->withCount(['comments', 'likes'])
                  ->simplePaginate(21);

      return view('profile.show', compact('user', 'settings', 'owner', 'is_following', 'is_admin', 'timeline'));
    }

    protected function privateProfileCheck(Profile $profile)
    {
      if(Auth::check() === false) {
        return false;
      }

      $follower_ids = (array) $profile->followers()->pluck('followers.profile_id');
      $pid = Auth::user()->profile->id;
      if(!in_array($pid, $follower_ids) && $pid !== $profile->id) {
        return false;
      }

      return true;
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
      $profile = Profile::whereUsername($username)->firstOrFail();
      // TODO: fix $profile/$user mismatch in profile & follower templates
      $user = $profile;
      $owner = Auth::check() && Auth::id() === $user->user_id;
      $is_following = ($owner == false && Auth::check()) ? $user->followedBy(Auth::user()->profile) : false;
      $followers = $profile->followers()->orderBy('created_at','desc')->simplePaginate(12);
      $is_admin = is_null($user->domain) ? $user->user->is_admin : false;
      return view('profile.followers', compact('user', 'profile', 'followers', 'owner', 'is_following', 'is_admin'));
    }

    public function following(Request $request, $username)
    {
      $profile = Profile::whereUsername($username)->firstOrFail();
      // TODO: fix $profile/$user mismatch in profile & follower templates
      $user = $profile;
      $owner = Auth::check() && Auth::id() === $user->user_id;
      $is_following = ($owner == false && Auth::check()) ? $user->followedBy(Auth::user()->profile) : false;
      $following = $profile->following()->orderBy('created_at','desc')->simplePaginate(12);
      $is_admin = is_null($user->domain) ? $user->user->is_admin : false;
      return view('profile.following', compact('user', 'profile', 'following', 'owner', 'is_following', 'is_admin'));
    }

    public function savedBookmarks(Request $request, $username)
    {
      if(Auth::check() === false || $username !== Auth::user()->username) {
        abort(403);
      }
      $user = Auth::user()->profile;
      $settings = User::whereUsername($username)->firstOrFail()->settings;
      $owner = true;
      $following = false;
      $timeline = $user->bookmarks()->withCount(['likes','comments'])->orderBy('created_at','desc')->simplePaginate(10);
      $is_following = ($owner == false && Auth::check()) ? $user->followedBy(Auth::user()->profile) : false;
      $is_admin = is_null($user->domain) ? $user->user->is_admin : false;
      return view('profile.show', compact('user', 'settings', 'owner', 'following', 'timeline', 'is_following', 'is_admin'));
    }
}
