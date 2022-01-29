<?php

namespace App\Http\Controllers;

use App\Bookmark;
use App\Status;
use Auth;
use Illuminate\Http\Request;
use App\Services\BookmarkService;

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
