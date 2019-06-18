<?php

namespace App\Http\Controllers;

use App\Jobs\LikePipeline\LikePipeline;
use App\Like;
use App\Status;
use App\User;
use Auth;
use Cache;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'item'    => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        $profile = $user->profile;
        $status = Status::withCount('likes')->findOrFail($request->input('item'));

        $count = $status->likes_count;

        if ($status->likes()->whereProfileId($profile->id)->count() !== 0) {
            $like = Like::whereProfileId($profile->id)->whereStatusId($status->id)->firstOrFail();
            $like->forceDelete();
            $count--;
            if($count >= 0) {
                $status->likes_count = $count;
                $status->save();
            }
        } else {
            $like = new Like();
            $like->profile_id = $profile->id;
            $like->status_id = $status->id;
            $like->save();
            $count++;
            if($count >= 0) {
                $status->likes_count = $count;
                $status->save();
            }
            LikePipeline::dispatch($like);
        }

        Cache::forget('status:'.$status->id.':likedby:userid:'.$user->id);

        if ($request->ajax()) {
            $response = ['code' => 200, 'msg' => 'Like saved', 'count' => $count];
        } else {
            $response = redirect($status->url());
        }

        return $response;
    }
}
