<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\UserInvite;
use Illuminate\Support\Str;

class UserInviteController extends Controller
{
	public function create(Request $request)
	{
		abort_if(!config('pixelfed.user_invites.enabled'), 404);
		abort_unless(Auth::check(), 403);
		return view('settings.invites.create');
	}

	public function show(Request $request)
	{
		abort_if(!config('pixelfed.user_invites.enabled'), 404);
		abort_unless(Auth::check(), 403);
		$invites = UserInvite::whereUserId(Auth::id())->paginate(10);
		$limit = config('pixelfed.user_invites.limit.total');
		$used = UserInvite::whereUserId(Auth::id())->count();
		return view('settings.invites.home', compact('invites', 'limit', 'used'));
	}

	public function store(Request $request)
	{
		abort_if(!config('pixelfed.user_invites.enabled'), 404);
		abort_unless(Auth::check(), 403);
		$this->validate($request, [
			'email' => 'required|email|unique:users|unique:user_invites',
			'message' => 'nullable|string|max:500',
			'tos'	=> 'required|accepted'
		]);

		$invite = new UserInvite;
		$invite->user_id = Auth::id();
		$invite->profile_id = Auth::user()->profile->id;
		$invite->email = $request->input('email');
		$invite->message = $request->input('message');
		$invite->key = (string) Str::uuid();
		$invite->token = str_random(32);
		$invite->save();

		return redirect(route('settings.invites'));
	}
}
