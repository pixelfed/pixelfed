<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ProfileSponsor;
use Auth;

class ProfileSponsorController extends Controller
{
	public function get(Request $request, $id)
	{
		$res = ProfileSponsor::whereProfileId($id)->firstOrFail()->sponsors;
		return response($res)->header('Content-Type', 'application/json');
	}
}
