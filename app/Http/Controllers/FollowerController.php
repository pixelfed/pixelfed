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
            abort(400, 'You cannot follow this user.');
        }

        $isFollowing = Follower::whereProfileId($user->id)->whereFollowingId($target->id)->count();

        if($private == true && $isFollowing == 0 || $remote == true) {
            $follow = FollowRequest::firstOrCreate([
                'follower_id' => $user->id,
                'following_id' => $target->id
            ]);
            if($remote == true) {
                
            }
        } elseif ($isFollowing == 0) {
            if($user->following()->count() >= Follower::MAX_FOLLOWING) {
                abort(400, 'You cannot follow more than ' . Follower::MAX_FOLLOWING . ' accounts');
            }

            if($user->following()->where('followers.created_at', '>', now()->subHour())->count() >= Follower::FOLLOW_PER_HOUR) {
                abort(400, 'You can only follow ' . Follower::FOLLOW_PER_HOUR . ' users per hour');
            }
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
        Cache::forget('api:local:exp:rec:'.$user->id);
        Cache::forget('user:account:id:'.$target->user_id);
        Cache::forget('user:account:id:'.$user->user_id);
    }
}
