<?php

namespace App\Http\Controllers;

use App\Bookmark;
use App\Status;
use Auth;
use Illuminate\Http\Request;
use App\Services\BookmarkService;
use App\Services\FollowerService;

class BookmarkController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'item' => 'required|integer|min:1',
        ]);

        $profile = Auth::user()->profile;
        $status = Status::findOrFail($request->input('item'));

        abort_if(!in_array($status->scope, ['public', 'unlisted', 'private']), 404);

        if($status->scope == 'private') {
            abort_if(
                $profile->id !== $status->profile_id && !FollowerService::follows($profile->id, $status->profile_id),
                404,
                'Error: You cannot bookmark private posts from accounts you do not follow.'
            );
        }

        $bookmark = Bookmark::firstOrCreate(
            ['status_id' => $status->id], ['profile_id' => $profile->id]
        );

        if (!$bookmark->wasRecentlyCreated) {
        	BookmarkService::del($profile->id, $status->id);
            $bookmark->delete();
        } else {
        	BookmarkService::add($profile->id, $status->id);
        }

        if ($request->ajax()) {
            $response = ['code' => 200, 'msg' => 'Bookmark saved!'];
        } else {
            $response = redirect()->back();
        }

        return $response;
    }
}
