<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

class ContactController extends Controller
{
	public function show(Request $request)
	{
		return view('site.contact');
	}
	
	public function store(Request $request)
	{
		abort_if(!Auth::check(), 403);

		$this->validate($request, [
			'message' => 'required|string|min:5|max:500',
			'request_response' => 'string|max:3'
		]);

		$message = $request->input('message');
		$request_response = $request->input('request_response') == 'on' ? true : false;
		$user = Auth::user();
		return $request->all();
	}
}
