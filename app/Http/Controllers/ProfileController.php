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

      $mimes = [
        'application/activity+json', 
        'application/ld+json',
        'application/ld+json; profile="https://www.w3.org/ns/activitystreams"'
      ];

      if(in_array($request->header('accept'), $mimes)) {
        return $this->showActivityPub($request, $user);
      }

      // TODO: refactor this mess
      $owner = Auth::check() && Auth::id() === $user->user_id;
      $following = ($owner == false && Auth::check()) ? $user->followedBy(Auth::user()->profile) : false;
      $timeline = $user->statuses()->whereHas('media')->whereNull('in_reply_to_id')->orderBy('id','desc')->paginate(21);

      return view('profile.show', compact('user', 'owner', 'following', 'timeline'));
    }

    public function showActivityPub(Request $request, $user)
    {
      $fractal = new Fractal\Manager();
      $resource = new Fractal\Resource\Item($user, new ProfileTransformer);
      $res = $fractal->createData($resource)->toArray();
      return response()->json($res['data']);
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
      $followers = $profile->followers()->orderBy('created_at','desc')->simplePaginate(12);
      return view('profile.followers', compact('profile', 'followers'));
    }

    public function following(Request $request, $username)
    {
      $profile = Profile::whereUsername($username)->firstOrFail();
      $following = $profile->following()->orderBy('created_at','desc')->simplePaginate(12);
      return view('profile.following', compact('profile', 'following'));
    }

    public function savedBookmarks(Request $request, $username)
    {
      if(Auth::check() === false || $username !== Auth::user()->username) {
        abort(403);
      }
      $user = Auth::user()->profile;
      $owner = true;
      $following = false;
      $timeline = $user->bookmarks()->orderBy('created_at','desc')->simplePaginate(10);
      return view('profile.show', compact('user', 'owner', 'following', 'timeline'));
    }
}
