<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Mail\ConfirmEmail;
use Auth, DB, Cache, Mail, Redis;
use App\{EmailVerification, Notification, Profile, User};

class AccountController extends Controller
{
    public function __construct()
    {
      $this->middleware('auth');
    }

    public function notifications(Request $request)
    {
      $this->validate($request, [
          'page' => 'nullable|min:1|max:3'
      ]);
      $profile = Auth::user()->profile;
      $timeago = Carbon::now()->subMonths(6);
      $notifications = Notification::whereProfileId($profile->id)
          ->whereDate('created_at', '>', $timeago)
          ->orderBy('id','desc')
          ->take(30)
          ->simplePaginate();

      return view('account.activity', compact('profile', 'notifications'));
    }

    public function verifyEmail(Request $request)
    {
      return view('account.verify_email');
    }

    public function sendVerifyEmail(Request $request)
    {
        if(EmailVerification::whereUserId(Auth::id())->count() !== 0) {
            return redirect()->back()->with('status', 'A verification email has already been sent! Please check your email.');
        }
        
        $user = User::whereNull('email_verified_at')->find(Auth::id());
        $utoken = hash('sha512', $user->id);
        $rtoken = str_random(40);

        $verify = new EmailVerification;
        $verify->user_id = $user->id;
        $verify->email = $user->email;
        $verify->user_token = $utoken;
        $verify->random_token = $rtoken;
        $verify->save();

        Mail::to($user->email)->send(new ConfirmEmail($verify));

        return redirect()->back()->with('status', 'Email verification email sent!');
    }

    public function confirmVerifyEmail(Request $request, $userToken, $randomToken)
    {
        $verify = EmailVerification::where(DB::raw('BINARY `user_token`'), $userToken)
          ->where(DB::raw('BINARY `random_token`'), $randomToken)
          ->firstOrFail();
        if(Auth::id() === $verify->user_id) {
          $user = User::find(Auth::id());
          $user->email_verified_at = Carbon::now();
          $user->save();
          return redirect('/timeline');
        }
    }

    public function fetchNotifications($id)
    {
      $key = config('cache.prefix') . ":user.{$id}.notifications";
      $redis = Redis::connection();
      $notifications = $redis->lrange($key, 0, 30);
      if(empty($notifications)) {
        $notifications = Notification::whereProfileId($id)
          ->orderBy('id','desc')->take(30)->get();
      } else {
        $notifications = $this->hydrateNotifications($notifications);
      }

      return $notifications;
    }

    public function hydrateNotifications($keys)
    {
      $prefix = 'notification.';
      $notifications = collect([]);
      foreach($keys as $key) {
        $notifications->push(Cache::get("{$prefix}{$key}"));
      }
      return $notifications;
    }
}
