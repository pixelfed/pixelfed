<?php

namespace App\Http\Controllers;

use App\EmailVerification;
use App\Follower;
use App\FollowRequest;
use App\Jobs\FollowPipeline\FollowPipeline;
use App\Mail\ConfirmEmail;
use App\Notification;
use App\Profile;
use App\User;
use App\UserFilter;
use Auth;
use Cache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Mail;
use Redis;
use PragmaRX\Google2FA\Google2FA;

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
        $this->validate($request, [
          'page' => 'nullable|min:1|max:3',
          'a'    => 'nullable|alpha_dash',
      ]);
        $profile = Auth::user()->profile;
        $action = $request->input('a');
        $timeago = Carbon::now()->subMonths(6);
        if ($action && in_array($action, ['comment', 'follow', 'mention'])) {
            $notifications = Notification::whereProfileId($profile->id)
            ->whereAction($action)
            ->whereDate('created_at', '>', $timeago)
            ->orderBy('id', 'desc')
            ->simplePaginate(30);
        } else {
            $notifications = Notification::whereProfileId($profile->id)
            ->whereDate('created_at', '>', $timeago)
            ->orderBy('id', 'desc')
            ->simplePaginate(30);
        }

        return view('account.activity', compact('profile', 'notifications'));
    }

    public function followingActivity(Request $request)
    {
        $this->validate($request, [
          'page' => 'nullable|min:1|max:3',
          'a'    => 'nullable|alpha_dash',
      ]);
        $profile = Auth::user()->profile;
        $action = $request->input('a');
        $allowed = ['like', 'follow'];
        $timeago = Carbon::now()->subMonths(3);
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
        $timeLimit = Carbon::now()->subDays(1)->toDateTimeString();
        $recentAttempt = EmailVerification::whereUserId(Auth::id())
          ->where('created_at', '>', $timeLimit)->count();
        $exists = EmailVerification::whereUserId(Auth::id())->count();

        if ($recentAttempt == 1 && $exists == 1) {
            return redirect()->back()->with('error', 'A verification email has already been sent recently. Please check your email, or try again later.');
        } elseif ($recentAttempt == 0 && $exists !== 0) {
            // Delete old verification and send new one.
            EmailVerification::whereUserId(Auth::id())->delete();
        }

        $user = User::whereNull('email_verified_at')->find(Auth::id());
        $utoken = hash('sha512', $user->id);
        $rtoken = str_random(40);

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
          ->where('random_token', $randomToken)
          ->firstOrFail();

        if (Auth::id() === $verify->user_id) {
            $user = User::find(Auth::id());
            $user->email_verified_at = Carbon::now();
            $user->save();

            return redirect('/');
        }
    }

    public function fetchNotifications($id)
    {
        $key = config('cache.prefix').":user.{$id}.notifications";
        $redis = Redis::connection();
        $notifications = $redis->lrange($key, 0, 30);
        if (empty($notifications)) {
            $notifications = Notification::whereProfileId($id)
          ->orderBy('id', 'desc')->take(30)->get();
        } else {
            $notifications = $this->hydrateNotifications($notifications);
        }

        return $notifications;
    }

    public function hydrateNotifications($keys)
    {
        $prefix = 'notification.';
        $notifications = collect([]);
        foreach ($keys as $key) {
            $notifications->push(Cache::get("{$prefix}{$key}"));
        }

        return $notifications;
    }

    public function messages()
    {
        return view('account.messages');
    }

    public function showMessage(Request $request, $id)
    {
        return view('account.message');
    }

    public function mute(Request $request)
    {
        $this->validate($request, [
          'type' => 'required|string',
          'item' => 'required|integer|min:1',
        ]);

        $user = Auth::user()->profile;
        $type = $request->input('type');
        $item = $request->input('item');
        $action = "{$type}.mute";

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
            // code...
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
        Cache::forget("feature:discover:people:$pid");
        Cache::forget("feature:discover:posts:$pid");

        return redirect()->back();
    }

    public function block(Request $request)
    {
        $this->validate($request, [
          'type' => 'required|string',
          'item' => 'required|integer|min:1',
        ]);

        $user = Auth::user()->profile;
        $type = $request->input('type');
        $item = $request->input('item');
        $action = "{$type}.block";
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

          default:
            // code...
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
        Cache::forget("feature:discover:people:$pid");
        Cache::forget("feature:discover:posts:$pid");
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
                        // remove code
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
        //
    }
}
