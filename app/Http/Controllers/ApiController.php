<?php

namespace App\Http\Controllers;

use Auth, Cache;
use App\{Like, Status};
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseApiController;

class ApiController extends BaseApiController
{

    public function hydrateLikes(Request $request)
    {
        $this->validate($request, [
            'min' => 'nullable|integer|min:1',
            'max' => 'nullable|integer',
        ]);

        $profile = Auth::user()->profile;
        $res = Cache::remember('api:like-ids:user:'.$profile->id, 1440, function() use ($profile) {
            return Like::whereProfileId($profile->id)
                 ->orderBy('id', 'desc')
                 ->take(1000)
                 ->pluck('status_id');
        });

        return response()->json($res);
    }

    public function loadMoreComments(Request $request)
    {
        return;
    }
}
