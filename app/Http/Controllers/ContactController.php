<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Contact;

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

		$contact = Contact::whereUserId($user->id)
			->whereDate('created_at', '>', now()->subDays(1))
			->count();

		if($contact >= 2) {
			return redirect()->back()->with('error', 'You have recently sent a message. Please try again later.');
		}

		$contact = new Contact;
		$contact->user_id = $user->id;
		$contact->response_requested = $request_response;
		$contact->message = $message;
		$contact->save();

		return redirect()->back()->with('status', 'Success - Your message has been sent to admins.');
	}
}
