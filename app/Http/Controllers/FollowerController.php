<?php

namespace App\Http\Controllers;

use App\{
    Follower,
    FollowRequest,
    Profile,
    UserFilter
};
use Auth, Cache;
use Illuminate\Http\Request;
use App\Jobs\FollowPipeline\FollowPipeline;

class FollowerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'item'    => 'required|integer',
        ]);
        $item = $request->input('item');
        $this->handleFollowRequest($item);
        if($request->wantsJson()) {
            return response()->json([
                200
            ], 200);
        }
        return redirect()->back();
    }

    protected function handleFollowRequest($item)
    {
        $user = Auth::user()->profile;
        $target = Profile::where('id', '!=', $user->id)->whereNull('status')->findOrFail($item);
        $private = (bool) $target->is_private;
        $remote = (bool) $target->domain;
        $blocked = UserFilter::whereUserId($target->id)
                ->whereFilterType('block')
                ->whereFilterableId($user->id)
                ->whereFilterableType('App\Profile')
                ->exists();

        if($blocked == true) {
            return redirect()->back()->with('error', 'You cannot follow this user.');
        }

        $isFollowing = Follower::whereProfileId($user->id)->whereFollowingId($target->id)->count();

        if($private == true && $isFollowing == 0 || $remote == true) {
            $follow = FollowRequest::firstOrCreate([
                'follower_id' => $user->id,
                'following_id' => $target->id
            ]);
        } elseif ($isFollowing == 0) {
            $follower = new Follower();
            $follower->profile_id = $user->id;
            $follower->following_id = $target->id;
            $follower->save();
            FollowPipeline::dispatch($follower);
        } else {
            $follower = Follower::whereProfileId($user->id)->whereFollowingId($target->id)->firstOrFail();
            $follower->delete();
        }

        Cache::forget('profile:following:'.$target->id);
        Cache::forget('profile:followers:'.$target->id);
        Cache::forget('profile:following:'.$user->id);
        Cache::forget('profile:followers:'.$user->id);
    }
}
