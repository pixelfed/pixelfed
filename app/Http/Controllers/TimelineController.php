<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TimelineController extends Controller
{
    public function __construct()
    {
      $this->middleware('auth');
    }

    public function personal()
    {
      return view('timeline.personal');
    }
}
