<?php

namespace App\Http\Controllers;

use Auth;
use App\Like;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function hydrateLikes(Request $request)
    {
        $this->validate($request, [
            'min' => 'nullable|integer|min:1',
            'max' => 'nullable|integer',
        ]);

        $profile = Auth::user()->profile;

        $likes = Like::whereProfileId($profile->id)
                 ->orderBy('id', 'desc')
                 ->take(100)
                 ->pluck('status_id');
                 
        return response()->json($likes);
    }
}
