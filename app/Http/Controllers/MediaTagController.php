<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MediaTagService;
use App\MediaTag;
use App\Notification;
use App\Profile;
use App\UserFilter;
use App\User;
use Illuminate\Support\Str;

class MediaTagController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function usernameLookup(Request $request)
    {
        abort(404);
    }

    public function untagProfile(Request $request)
    {
        abort_if(!$request->user(), 403);

        $this->validate($request, [
            'status_id' => 'required',
            'profile_id' => 'required'
        ]);

        $user = $request->user();
        $status_id = $request->input('status_id');
        $profile_id = (int) $request->input('profile_id');

        abort_if((int) $user->profile_id !== $profile_id, 400);

        $tag = MediaTag::whereStatusId($status_id)
            ->whereProfileId($profile_id)
            ->first();

        if(!$tag) {
            return [];
        }
        Notification::whereItemType('App\MediaTag')
            ->whereItemId($tag->id)
            ->whereProfileId($profile_id)
            ->whereAction('tagged')
            ->delete();

        MediaTagService::untag($status_id, $profile_id);

        return [200];

    }
}
