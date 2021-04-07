<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TimelineController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth');
		$this->middleware('twofactor');
	}

	public function local(Request $request)
	{
		$this->validate($request, [
			'layout' => 'nullable|string|in:grid,feed'
		]);
		$layout = $request->input('layout', 'feed');
		return view('timeline.local', compact('layout'));
	}

	public function network(Request $request)
	{
		abort_if(config('federation.network_timeline') == false, 404);
		$this->validate($request, [
			'layout' => 'nullable|string|in:grid,feed'
		]);
		$layout = $request->input('layout', 'feed');
		return view('timeline.network', compact('layout'));
	}
}
