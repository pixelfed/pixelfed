<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Http\Controllers;

use App\Jobs\LikePipeline\LikePipeline;
use App\Like;
use App\Status;
use App\User;
use Auth;
use Cache;
use Illuminate\Http\Request;
use App\Services\StatusService;

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
        $status = Status::findOrFail($request->input('item'));

        $count = $status->likes()->count();

        if ($status->likes()->whereProfileId($profile->id)->count() !== 0) {
            $like = Like::whereProfileId($profile->id)->whereStatusId($status->id)->firstOrFail();
            $like->forceDelete();
            $count--;
            $status->likes_count = $count;
            $status->save();
        } else {
            $like = Like::firstOrCreate([
                'profile_id' => $user->profile_id,
                'status_id' => $status->id
            ]);
            if($like->wasRecentlyCreated == true) {
                $count++;
                $status->likes_count = $count;
                $like->status_profile_id = $status->profile_id;
                $like->is_comment = in_array($status->type, [
                    'photo',
                    'photo:album',
                    'video',
                    'video:album',
                    'photo:video:album'
                    ]) == false;
                $like->save();
                $status->save();
                LikePipeline::dispatch($like);
            }
        }

        Cache::forget('status:'.$status->id.':likedby:userid:'.$user->id);
        StatusService::del($status->id);

        if ($request->ajax()) {
            $response = ['code' => 200, 'msg' => 'Like saved', 'count' => $count];
        } else {
            $response = redirect($status->url());
        }

        return $response;
    }
}
