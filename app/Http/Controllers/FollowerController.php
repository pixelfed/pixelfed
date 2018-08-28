<?php

namespace App\Http\Controllers;

use App\Follower;
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

        $user = Auth::user()->profile;
        $target = Profile::where('id', '!=', $user->id)->findOrFail($request->input('item'));

        $isFollowing = Follower::whereProfileId($user->id)->whereFollowingId($target->id)->count();

        if ($isFollowing == 0) {
            $follower = new Follower();
            $follower->profile_id = $user->id;
            $follower->following_id = $target->id;
            $follower->save();
            FollowPipeline::dispatch($follower);
        } else {
            $follower = Follower::whereProfileId($user->id)->whereFollowingId($target->id)->firstOrFail();
            $follower->delete();
        }

        return redirect()->back();
    }
}
