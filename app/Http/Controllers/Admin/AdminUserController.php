<?php

namespace App\Http\Controllers\Admin;

use Cache, DB;
use Illuminate\Http\Request;
use App\ModLog;
use App\User;
use App\Mail\AdminMessage;
use Illuminate\Support\Facades\Mail;
use App\Services\ModLogService;

trait AdminUserController
{
	public function users(Request $request)
	{
		$col = $request->query('col') ?? 'id';
		$dir = $request->query('dir') ?? 'desc';
		$users = User::select('id', 'username', 'status')
			->withCount('statuses')
			->orderBy($col, $dir)
			->simplePaginate(10);

		return view('admin.users.home', compact('users'));
	}

	public function userShow(Request $request, $id)
	{
		$user = User::findOrFail($id);
		$profile = $user->profile;
		return view('admin.users.show', compact('user', 'profile'));
	}

	public function userEdit(Request $request, $id)
	{
		$user = User::findOrFail($id);
		$profile = $user->profile;
		return view('admin.users.edit', compact('user', 'profile'));
	}

	public function userEditSubmit(Request $request, $id)
	{
		$user = User::findOrFail($id);
		$profile = $user->profile;
		$changed = false;

		if($request->filled('name') && $request->input('name') != $user->name) {
			$user->name = $profile->name = $request->input('name');
			$changed = true;
		}
		if($request->filled('username') && $request->input('username') != $user->username) {
			$user->username = $profile->username = $request->input('username');
			$changed = true;
		}
		if($request->filled('email') && $request->input('email') != $user->email) {
			if(filter_var($request->input('email'), FILTER_VALIDATE_EMAIL) == false) {
				abort(500, 'Invalid email address');
			}
			$user->email = $request->input('email');
			$changed = true;
		}
		if($request->input('bio') != $profile->bio) {
			$profile->bio = $request->input('bio');
			$changed = true;
		}
		if($request->input('website') != $profile->website) {
			$profile->website = $request->input('website');
			$changed = true;
		}

		if($changed == true) {
			$profile->save();
			$user->save();
		}
		return redirect('/i/admin/users/show/' . $user->id);
	}

	public function userActivity(Request $request, $id)
	{
		$user = User::findOrFail($id);
		$profile = $user->profile;
		$logs = $user->accountLog()->orderByDesc('created_at')->paginate(10);
		return view('admin.users.activity', compact('user', 'profile', 'logs'));
	}

	public function userMessage(Request $request, $id)
	{
		$user = User::findOrFail($id);
		$profile = $user->profile;
		return view('admin.users.message', compact('user', 'profile'));
	}

	public function userMessageSend(Request $request, $id)
	{
		$this->validate($request, [
			'message' => 'required|string|min:5|max:500'
		]);
		$user = User::findOrFail($id);
		$profile = $user->profile;
		$message = $request->input('message');
		Mail::to($user->email)->send(new AdminMessage($message));
		ModLogService::boot()
			->objectUid($user->id)
			->objectId($user->id)
			->objectType('App\User::class')
			->user($request->user())
			->action('admin.user.mail')
			->metadata([
				'message' => $message
			])
			->accessLevel('admin')
			->save();
		return redirect('/i/admin/users/show/' . $user->id);
	}

	public function userModTools(Request $request, $id)
	{
		$user = User::findOrFail($id);
		$profile = $user->profile;
		return view('admin.users.modtools', compact('user', 'profile'));
	}

	public function userModLogs(Request $request, $id)
	{
		$user = User::findOrFail($id);
		$profile = $user->profile;
		$logs = ModLog::whereObjectUid($user->id)
			->orderByDesc('created_at')
			->simplePaginate(10);
		return view('admin.users.modlogs', compact('user', 'profile', 'logs'));
	}

	public function userModLogsMessage(Request $request, $id)
	{
		$this->validate($request, [
			'message' => 'required|string|min:5|max:500'
		]);
		$user = User::findOrFail($id);
		$profile = $user->profile;
		$msg = $request->input('message');
		ModLogService::boot()
			->objectUid($user->id)
			->objectId($user->id)
			->objectType('App\User::class')
			->user($request->user())
			->message($msg)
			->accessLevel('admin')
			->save();
		return redirect('/i/admin/users/modlogs/' . $user->id);
	}

	public function userDelete(Request $request, $id)
	{
		$user = User::findOrFail($id);
		$profile = $user->profile;
		return view('admin.users.delete', compact('user', 'profile'));
	}
}