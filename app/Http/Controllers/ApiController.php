<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\BaseApiController;
use App\Like;
use Auth;
use Cache;
use Illuminate\Http\Request;

class ApiController extends BaseApiController
{
    // todo: deprecate and remove
    public function hydrateLikes(Request $request)
    {
        $this->validate($request, [
            'min' => 'nullable|integer|min:1',
            'max' => 'nullable|integer',
        ]);

        $profile = Auth::user()->profile;
        $res = Cache::remember('api:like-ids:user:'.$profile->id, now()->addDays(1), function () use ($profile) {
            return Like::whereProfileId($profile->id)
                 ->orderBy('id', 'desc')
                 ->take(1000)
                 ->pluck('status_id');
        });

        return response()->json($res);
    }

    public function loadMoreComments(Request $request)
    {
    }
}
