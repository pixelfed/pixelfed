<?php

namespace App\Http\Controllers;

use App\Follower;
use App\FollowRequest;
use App\Jobs\FollowPipeline\FollowPipeline;
use App\Profile;
use Auth;
use Illuminate\Http\Request;

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
        return redirect()->back();
    }

    protected function handleFollowRequest($item)
    {
        $user = Auth::user()->profile;
        $target = Profile::where('id', '!=', $user->id)->findOrFail($item);
        $private = (bool) $target->is_private;
        $isFollowing = Follower::whereProfileId($user->id)->whereFollowingId($target->id)->count();

        if($private == true && $isFollowing == 0) {
            $follow = new FollowRequest;
            $follow->follower_id = $user->id;
            $follow->following_id = $target->id;
            $follow->save();
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
    }
}
