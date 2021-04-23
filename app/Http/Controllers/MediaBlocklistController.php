<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\MediaBlocklist;

class MediaBlocklistController extends Controller
{
    public function __construct()
    {
    	$this->middleware('auth');
    	$this->middleware('admin');
    }

    public function add(Request $request)
    {
    	$this->validate($request, [
    		'hash' => 'required|string|size:64',
    		'name' => 'nullable|string',
    		'description' => 'nullable|string|max:500',
    	]);

    	$hash = $request->input('hash');
    	abort_if(preg_match("/^([a-f0-9]{64})$/", $hash) !== 1, 400);

    	$name = $request->input('name');
    	$description = $request->input('description');

    	$mb = new MediaBlocklist;
    	$mb->sha256 = $hash;
    	$mb->name = $name;
    	$mb->description = $description;
    	$mb->save();

    	return redirect('/i/admin/media?layout=banned');
    }

    public function delete(Request $request)
    {
        $this->validate($request, [
            'id'    => 'required|integer'
        ]);

        $media = MediaBlocklist::findOrFail($request->input('id'));
        $media->delete();

        return redirect('/i/admin/media?layout=banned');
    }
}
