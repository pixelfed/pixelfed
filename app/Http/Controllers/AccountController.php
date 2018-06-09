<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Auth, Cache, Redis;
use App\{Notification, Profile, User};

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
