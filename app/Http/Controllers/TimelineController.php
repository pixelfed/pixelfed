<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{Status, User};

class TimelineController extends Controller
{
    public function __construct()
    {
      $this->middleware('auth');
    }

    public function personal()
    {
      $timeline = Status::orderBy('id','desc')->paginate(10);
      return view('timeline.personal', compact('timeline'));
    }
}
