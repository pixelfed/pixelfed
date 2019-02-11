<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StoryController extends Controller
{

	public function construct()
	{
		$this->middleware('auth');
	}

	public function home(Request $request)
	{
		return view('stories.home');
	}
}
