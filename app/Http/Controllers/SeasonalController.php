<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

class SeasonalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function yearInReview()
    {
        $profile = Auth::user()->profile;
        return view('account.yir', compact('profile'));
    }
}
