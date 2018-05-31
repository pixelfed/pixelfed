<?php

namespace App\Http\Controllers;

use Auth;
use App\{Bookmark, Profile, Status};
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
          'item' => 'required|integer|min:1'
        ]);

        $profile = Auth::user()->profile;
        $status = Status::findOrFail($request->input('item'));

        $bookmark = Bookmark::firstOrCreate(
          ['status_id' => $status->id], ['profile_id' => $profile->id]
        );

        if($request->ajax()) {
          $response = ['code' => 200, 'msg' => 'Bookmark saved!'];
        } else {
          $response = redirect()->back();
        }

        return $response;
    }

}
