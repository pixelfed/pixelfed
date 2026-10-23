<?php

namespace App\Http\Controllers;

use App\Models\ProfileSponsor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileSponsorController extends Controller
{
    public function get(Request $request, $id): JsonResponse
    {
        $profile = ProfileSponsor::whereProfileId($id)->first();
        $res = $profile ? $profile->sponsors : [];

        return response()->json($res);
    }
}
