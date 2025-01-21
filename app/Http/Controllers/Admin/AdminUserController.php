<?php

namespace App\Http\Controllers\Admin;

use Cache, DB;
use Illuminate\Http\Request;
use App\ModLog;
use App\Profile;
use App\User;
use App\Mail\AdminMessage;
use Illuminate\Support\Facades\Mail;
use App\Services\ModLogService;
use App\Jobs\DeletePipeline\DeleteAccountPipeline;
use App\Services\AccountService;

trait AdminUserController
{
	public function users(Request $request)
	{
		$search = $request->has('a') && $request->query('a') == 'search' ? $request->query('q') : null;
		$col = $request->query('col') ?? 'id';
		$dir = $request->query('dir') ?? 'desc';
		$offset = $request->has('page') ? $request->input('page') : 0;
		$pagination = [
			'prev' => $offset > 0 ? $offset - 1 : null,
			'next' => $offset + 1,
			'query' => $search ? '&a=search&q=' . $search : null
		];
		$users = User::select('id', 'username', 'status', 'profile_id', 'is_admin')
			->orderBy($col, $dir)
			->when($search, function($q, $search) {
				return $q->where('username', 'like', "%{$search}%");
			})
			->when($offset, function($q, $offset) {
				return $q->offset(($offset * 10));
			})
			->limit(10)
			->get()
			->map(function($u) {
				$u['account'] = AccountService::get($u->profile_id, true);
				return $u;
			});

		return view('admin.users.home', compact('users', 'pagination'));
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
		$fields = [];

		if($request->filled('name') && $request->input('name') != $user->name) {
			$fields['name'] = ['old' => $user->name, 'new' => $request->input('name')];
			$user->name = $profile->name = $request->input('name');
			$changed = true;
		}
		if($request->filled('username') && $request->input('username') != $user->username) {
			$fields['username'] = ['old' => $user->username, 'new' => $request->input('username')];
			$user->username = $profile->username = $request->input('username');
			$changed = true;
		}
		if($request->filled('email') && $request->input('email') != $user->email) {
			if(filter_var($request->input('email'), FILTER_VALIDATE_EMAIL) == false) {
				abort(500, 'Invalid email address');
			}
			$fields['email'] = ['old' => $user->email, 'new' => $request->input('email')];
			$user->email = $request->input('email');
			$changed = true;
		}
		if($request->input('bio') != $profile->bio) {
			$fields['bio'] = ['old' => $user->bio, 'new' => $request->input('bio')];
			$profile->bio = $request->input('bio');
			$changed = true;
		}
		if($request->input('website') != $profile->website) {
			$fields['website'] = ['old' => $user->website, 'new' => $request->input('website')];
			$profile->website = $request->input('website');
			$changed = true;
		}

		if($changed == true) {
			ModLogService::boot()
				->objectUid($user->id)
				->objectId($user->id)
				->objectType('App\User::class')
				->user($request->user())
				->action('admin.user.edit')
				->metadata([
					'fields' => $fields
				])
				->accessLevel('admin')
				->save();
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

	public function userDeleteProcess(Request $request, $id)
	{
		$user = User::findOrFail($id);
		$profile = $user->profile;

		if(config('pixelfed.account_deletion') == false) {
			abort(404);
		}

		if($user->is_admin == true) {
			$mid = $request->user()->id;
			abort_if($user->id < $mid, 403);
		}

		$ts = now()->addMonth();
		$user->status = 'delete';
		$profile->status = 'delete';
		$user->delete_after = $ts;
		$profile->delete_after = $ts;
		$user->save();
		$profile->save();

		ModLogService::boot()
			->objectUid($user->id)
			->objectId($user->id)
			->objectType('App\User::class')
			->user($request->user())
			->action('admin.user.delete')
			->accessLevel('admin')
			->save();

		Cache::forget('profiles:private');
		DeleteAccountPipeline::dispatch($user);

		$msg = "Successfully deleted {$user->username}!";
		$request->session()->flash('status', $msg);
		return redirect('/i/admin/users/list');
	}

	public function userModerate(Request $request)
	{
		$this->validate($request, [
			'profile_id' => 'required|exists:profiles,id',
			'action' => 'required|in:cw,no_autolink,unlisted'
		]);

		$pid = $request->input('profile_id');
		$action = $request->input('action');
		$profile = Profile::findOrFail($pid);

		if($profile->user->is_admin == true) {
			$mid = $request->user()->id;
			abort_if($profile->user_id < $mid, 403);
		}

		switch ($action) {
			case 'cw':
				$profile->cw = !$profile->cw;
				$msg = "Success!";
				break;

			case 'no_autolink':
				$profile->no_autolink = !$profile->no_autolink;
				$msg = "Success!";
				break;

			case 'unlisted':
				$profile->unlisted = !$profile->unlisted;
				$msg = "Success!";
				break;
		}

		$profile->save();

		ModLogService::boot()
			->objectUid($profile->user_id)
			->objectId($profile->user_id)
			->objectType('App\User::class')
			->user($request->user())
			->action('admin.user.moderate')
			->metadata([
				'action' => $action,
				'message' => $msg
			])
			->accessLevel('admin')
			->save();

		$request->session()->flash('status', $msg);
		return redirect('/i/admin/users/modtools/' . $profile->user_id);
	}

	public function userModLogDelete(Request $request, $id)
	{
		$this->validate($request, [
			'mid' => 'required|integer|exists:mod_logs,id'
		]);
		$user = User::findOrFail($id);
		$uid = $request->user()->id;
		$mid = $request->input('mid');
		$ml = ModLog::whereUserId($uid)->findOrFail($mid)->delete();
		$msg = "Successfully deleted modlog comment!";
		$request->session()->flash('status', $msg);
		return redirect('/i/admin/users/modlogs/' . $user->id);
	}
}
