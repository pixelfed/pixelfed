<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */
 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ProfileSponsor;
use Auth;

class ProfileSponsorController extends Controller
{
	public function get(Request $request, $id)
	{
		$profile = ProfileSponsor::whereProfileId($id)->first();
		$res = $profile ? $profile->sponsors : [];
		return response()->json($res);
	}
}
