<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Models\UserEmailForgot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserEmailForgotReminder;
use Illuminate\Support\Facades\RateLimiter;

class UserEmailForgotController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
        abort_unless(config('security.forgot-email.enabled'), 404);
    }

    public function index(Request $request)
    {
        abort_if($request->user(), 404);
        return view('auth.email.forgot');
    }

    public function store(Request $request)
    {
        $rules = [
            'username' => 'required|min:2|max:15|exists:users'
        ];

        $messages = [
            'username.exists' => 'This username is no longer active or does not exist!'
        ];

        if(config('captcha.enabled') || config('captcha.active.login') || config('captcha.active.register')) {
            $rules['h-captcha-response'] = 'required|captcha';
            $messages['h-captcha-response.required'] = 'You need to complete the captcha!';
        }

        $randomDelay = random_int(500000, 2000000);
        usleep($randomDelay);

        $this->validate($request, $rules, $messages);
        $check = self::checkLimits();

        if(!$check) {
            return redirect()->back()->withErrors([
                'username' => 'Please try again later, we\'ve reached our quota and cannot process any more requests at this time.'
            ]);
        }

        $user = User::whereUsername($request->input('username'))
            ->whereNotNull('email_verified_at')
            ->whereNull('status')
            ->whereIsAdmin(false)
            ->first();

        if(!$user) {
            return redirect()->back()->withErrors([
                'username' => 'Invalid username or account. It may not exist, or does not have a verified email, is an admin account or is disabled.'
            ]);
        }

        $exists = UserEmailForgot::whereUserId($user->id)
            ->where('email_sent_at', '>', now()->subHours(24))
            ->count();

        if($exists) {
            return redirect()->back()->withErrors([
                'username' => 'An email reminder was recently sent to this account, please try again after 24 hours!'
            ]);
        }

        return $this->storeHandle($request, $user);
    }

    protected function storeHandle($request, $user)
    {
        UserEmailForgot::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'email_sent_at' => now()
        ]);

        Mail::to($user->email)->send(new UserEmailForgotReminder($user));
        self::getLimits(true);
        return redirect()->back()->with(['status' => 'Successfully sent an email reminder!']);
    }

    public static function checkLimits()
    {
        $limits = self::getLimits();

        if(
            $limits['current']['hourly'] >= $limits['max']['hourly'] ||
            $limits['current']['daily'] >= $limits['max']['daily'] ||
            $limits['current']['weekly'] >= $limits['max']['weekly'] ||
            $limits['current']['monthly'] >= $limits['max']['monthly']
        ) {
            return false;
        }

        return true;
    }

    public static function getLimits($forget = false)
    {
        return [
            'max' => config('security.forgot-email.limits.max'),
            'current' => [
                'hourly' => self::activeCount(60, $forget),
                'daily' => self::activeCount(1440, $forget),
                'weekly' => self::activeCount(10080, $forget),
                'monthly' => self::activeCount(43800, $forget)
            ]
        ];
    }

    public static function activeCount($mins, $forget = false)
    {
        if($forget) {
            Cache::forget('pf:auth:forgot-email:active-count:dur-' . $mins);
        }
        return Cache::remember('pf:auth:forgot-email:active-count:dur-' . $mins, 14200, function() use($mins) {
            return UserEmailForgot::where('email_sent_at', '>', now()->subMinutes($mins))->count();
        });
    }
}
