<?php

namespace App\Http\Controllers;

use Auth;
use Cache;
use Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Mail\ConfirmEmail;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use App\Jobs\FollowPipeline\FollowPipeline;
use App\EmailVerification;
use App\Follower;
use App\FollowRequest;
use App\Notification;
use App\Profile;
use App\User;
use App\UserFilter;

class AccountController extends Controller
{
	protected $filters = [
		'user.mute',
		'user.block',
	];

	public function __construct()
	{
		$this->middleware('auth');
	}

	public function notifications(Request $request)
	{
		return view('account.activity');
	}

	public function followingActivity(Request $request)
	{
		$this->validate($request, [
			'page' => 'nullable|min:1|max:3',
			'a'    => 'nullable|alpha_dash',
		]);

		$action = $request->input('a');
		$allowed = ['like', 'follow'];
		$timeago = Carbon::now()->subMonths(3);

		$profile = Auth::user()->profile;
		$following = $profile->following->pluck('id');

		$notifications = Notification::whereIn('actor_id', $following)
		->whereIn('action', $allowed)
		->where('actor_id', '<>', $profile->id)
		->where('profile_id', '<>', $profile->id)
		->whereDate('created_at', '>', $timeago)
		->orderBy('notifications.created_at', 'desc')
		->simplePaginate(30);

		return view('account.following', compact('profile', 'notifications'));
	}

	public function verifyEmail(Request $request)
	{
		return view('account.verify_email');
	}

	public function sendVerifyEmail(Request $request)
	{
		$recentAttempt = EmailVerification::whereUserId(Auth::id())
		->whereDate('created_at', '>', now()->subHours(12))->count();

		if ($recentAttempt > 0) {
			return redirect()->back()->with('error', 'A verification email has already been sent recently. Please check your email, or try again later.');
		} 

		EmailVerification::whereUserId(Auth::id())->delete();

		$user = User::whereNull('email_verified_at')->find(Auth::id());
		$utoken = Str::uuid() . Str::random(mt_rand(5,9));
		$rtoken = Str::random(mt_rand(64, 70));

		$verify = new EmailVerification();
		$verify->user_id = $user->id;
		$verify->email = $user->email;
		$verify->user_token = $utoken;
		$verify->random_token = $rtoken;
		$verify->save();

		Mail::to($user->email)->send(new ConfirmEmail($verify));

		return redirect()->back()->with('status', 'Verification email sent!');
	}

	public function confirmVerifyEmail(Request $request, $userToken, $randomToken)
	{
		$verify = EmailVerification::where('user_token', $userToken)
		->where('created_at', '>', now()->subHours(24))
		->where('random_token', $randomToken)
		->firstOrFail();

		if (Auth::id() === $verify->user_id && $verify->user_token === $userToken && $verify->random_token === $randomToken) {
			$user = User::find(Auth::id());
			$user->email_verified_at = Carbon::now();
			$user->save();

			return redirect('/');
		} else {
			abort(403);
		}
	}

	public function messages()
	{
		return view('account.messages');
	}

	public function direct()
	{
		return view('account.direct');
	}

	public function showMessage(Request $request, $id)
	{
		return view('account.message');
	}

	public function mute(Request $request)
	{
		$this->validate($request, [
			'type' => 'required|alpha_dash',
			'item' => 'required|integer|min:1',
		]);

		$user = Auth::user()->profile;
		$type = $request->input('type');
		$item = $request->input('item');
		$action = $type . '.mute';

		if (!in_array($action, $this->filters)) {
			return abort(406);
		}
		$filterable = [];
		switch ($type) {
			case 'user':
			$profile = Profile::findOrFail($item);
			if ($profile->id == $user->id) {
				return abort(403);
			}
			$class = get_class($profile);
			$filterable['id'] = $profile->id;
			$filterable['type'] = $class;
			break;
		}

		$filter = UserFilter::firstOrCreate([
			'user_id'         => $user->id,
			'filterable_id'   => $filterable['id'],
			'filterable_type' => $filterable['type'],
			'filter_type'     => 'mute',
		]);

		$pid = $user->id;
		Cache::forget("user:filter:list:$pid");
		Cache::forget("feature:discover:posts:$pid");
		Cache::forget("api:local:exp:rec:$pid");

		return redirect()->back();
	}

	public function unmute(Request $request)
	{
		$this->validate($request, [
			'type' => 'required|alpha_dash',
			'item' => 'required|integer|min:1',
		]);

		$user = Auth::user()->profile;
		$type = $request->input('type');
		$item = $request->input('item');
		$action = $type . '.mute';

		if (!in_array($action, $this->filters)) {
			return abort(406);
		}
		$filterable = [];
		switch ($type) {
			case 'user':
			$profile = Profile::findOrFail($item);
			if ($profile->id == $user->id) {
				return abort(403);
			}
			$class = get_class($profile);
			$filterable['id'] = $profile->id;
			$filterable['type'] = $class;
			break;

			default:
			abort(400);
			break;
		}

		$filter = UserFilter::whereUserId($user->id)
		->whereFilterableId($filterable['id'])
		->whereFilterableType($filterable['type'])
		->whereFilterType('mute')
		->first();

		if($filter) {
			$filter->delete();
		}

		$pid = $user->id;
		Cache::forget("user:filter:list:$pid");
		Cache::forget("feature:discover:posts:$pid");
		Cache::forget("api:local:exp:rec:$pid");

		if($request->wantsJson()) {
			return response()->json([200]);
		} else {
			return redirect()->back();
		}
	}

	public function block(Request $request)
	{
		$this->validate($request, [
			'type' => 'required|alpha_dash',
			'item' => 'required|integer|min:1',
		]);

		$user = Auth::user()->profile;
		$type = $request->input('type');
		$item = $request->input('item');
		$action = $type.'.block';
		if (!in_array($action, $this->filters)) {
			return abort(406);
		}
		$filterable = [];
		switch ($type) {
			case 'user':
			$profile = Profile::findOrFail($item);
			if ($profile->id == $user->id) {
				return abort(403);
			}
			$class = get_class($profile);
			$filterable['id'] = $profile->id;
			$filterable['type'] = $class;

			Follower::whereProfileId($profile->id)->whereFollowingId($user->id)->delete();
			Notification::whereProfileId($user->id)->whereActorId($profile->id)->delete();
			break;
		}

		$filter = UserFilter::firstOrCreate([
			'user_id'         => $user->id,
			'filterable_id'   => $filterable['id'],
			'filterable_type' => $filterable['type'],
			'filter_type'     => 'block',
		]);

		$pid = $user->id;
		Cache::forget("user:filter:list:$pid");
		Cache::forget("api:local:exp:rec:$pid");

		return redirect()->back();
	}

	public function unblock(Request $request)
	{
		$this->validate($request, [
			'type' => 'required|alpha_dash',
			'item' => 'required|integer|min:1',
		]);

		$user = Auth::user()->profile;
		$type = $request->input('type');
		$item = $request->input('item');
		$action = $type . '.block';
		if (!in_array($action, $this->filters)) {
			return abort(406);
		}
		$filterable = [];
		switch ($type) {
			case 'user':
			$profile = Profile::findOrFail($item);
			if ($profile->id == $user->id) {
				return abort(403);
			}
			$class = get_class($profile);
			$filterable['id'] = $profile->id;
			$filterable['type'] = $class;
			break;

			default:
			abort(400);
			break;
		}


		$filter = UserFilter::whereUserId($user->id)
		->whereFilterableId($filterable['id'])
		->whereFilterableType($filterable['type'])
		->whereFilterType('block')
		->first();

		if($filter) {
			$filter->delete();
		}

		$pid = $user->id;
		Cache::forget("user:filter:list:$pid");
		Cache::forget("feature:discover:posts:$pid");
		Cache::forget("api:local:exp:rec:$pid");

		return redirect()->back();
	}

	public function followRequests(Request $request)
	{
		$pid = Auth::user()->profile->id;
		$followers = FollowRequest::whereFollowingId($pid)->orderBy('id','desc')->whereIsRejected(0)->simplePaginate(10);
		return view('account.follow-requests', compact('followers'));
	}

	public function followRequestHandle(Request $request)
	{
		$this->validate($request, [
			'action' => 'required|string|max:10',
			'id' => 'required|integer|min:1'
		]);

		$pid = Auth::user()->profile->id;
		$action = $request->input('action') === 'accept' ? 'accept' : 'reject';
		$id = $request->input('id');
		$followRequest = FollowRequest::whereFollowingId($pid)->findOrFail($id);
		$follower = $followRequest->follower;

		switch ($action) {
			case 'accept':
			$follow = new Follower();
			$follow->profile_id = $follower->id;
			$follow->following_id = $pid;
			$follow->save();
			FollowPipeline::dispatch($follow);
			$followRequest->delete();
			break;

			case 'reject':
			$followRequest->is_rejected = true;
			$followRequest->save();
			break;
		}

		return response()->json(['msg' => 'success'], 200);
	}

	public function sudoMode(Request $request)
	{
        if($request->session()->has('sudoModeAttempts') && $request->session()->get('sudoModeAttempts') >= 3) {
        	$request->session()->pull('2fa.session.active');
            $request->session()->pull('redirectNext');
            $request->session()->pull('sudoModeAttempts');
            Auth::logout();
            return redirect(route('login'));
        } 
		return view('auth.sudo');
	}

	public function sudoModeVerify(Request $request)
	{
		$this->validate($request, [
			'password' => 'required|string|max:500'
		]);
		$user = Auth::user();
		$password = $request->input('password');
		$next = $request->session()->get('redirectNext', '/');
		if($request->session()->has('sudoModeAttempts')) {
			$count = (int) $request->session()->get('sudoModeAttempts');
			$request->session()->put('sudoModeAttempts', $count + 1);
		} else {
			$request->session()->put('sudoModeAttempts', 1);
		}
		if(password_verify($password, $user->password) === true) {
			$request->session()->put('sudoMode', time());
			return redirect($next);
		} else {
			return redirect()
			->back()
			->withErrors(['password' => __('auth.failed')]);
		}
	}

	public function twoFactorCheckpoint(Request $request)
	{
		return view('auth.checkpoint');
	}

	public function twoFactorVerify(Request $request)
	{
		$this->validate($request, [
			'code'  => 'required|string|max:32'
		]);
		$user = Auth::user();
		$code = $request->input('code');
		$google2fa = new Google2FA();
		$verify = $google2fa->verifyKey($user->{'2fa_secret'}, $code);
		if($verify) {
			$request->session()->push('2fa.session.active', true);
			return redirect('/');
		} else {

			if($this->twoFactorBackupCheck($request, $code, $user)) {
				return redirect('/');
			}

			if($request->session()->has('2fa.attempts')) {
				$count = (int) $request->session()->has('2fa.attempts');
				$request->session()->push('2fa.attempts', $count + 1);
			} else {
				$request->session()->push('2fa.attempts', 1);
			}
			return redirect()->back()->withErrors([
				'code' => 'Invalid code'
			]);
		}
	}

	protected function twoFactorBackupCheck($request, $code, User $user)
	{
		$backupCodes = $user->{'2fa_backup_codes'};
		if($backupCodes) {
			$codes = json_decode($backupCodes, true);
			foreach ($codes as $c) {
				if(hash_equals($c, $code)) {
					$codes = array_flatten(array_diff($codes, [$code]));
					$user->{'2fa_backup_codes'} = json_encode($codes);
					$user->save();
					$request->session()->push('2fa.session.active', true);
					return true;
				} else {
					return false;
				}
			}
		} else {
			return false;
		}  
	}

	public function accountRestored(Request $request)
	{
	}
}
