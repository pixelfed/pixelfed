<?php

namespace App\Http\Controllers;

use App\ProfileSponsor;
use Illuminate\Http\Request;

class ProfileSponsorController extends Controller
{
    public function get(Request $request, $id)
    {
        $profile = ProfileSponsor::whereProfileId($id)->first();
        $res = $profile ? $profile->sponsors : [];

        return response()->json($res);
    }
}
