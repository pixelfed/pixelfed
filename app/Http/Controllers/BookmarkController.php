<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Http\Controllers;

use App\Bookmark;
use App\Status;
use Auth;
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
            'item' => 'required|integer|min:1',
        ]);

        $profile = Auth::user()->profile;
        $status = Status::findOrFail($request->input('item'));

        $bookmark = Bookmark::firstOrCreate(
            ['status_id' => $status->id], ['profile_id' => $profile->id]
        );

        if (!$bookmark->wasRecentlyCreated) {
            $bookmark->delete();
        }

        if ($request->ajax()) {
            $response = ['code' => 200, 'msg' => 'Bookmark saved!'];
        } else {
            $response = redirect()->back();
        }

        return $response;
    }
}
