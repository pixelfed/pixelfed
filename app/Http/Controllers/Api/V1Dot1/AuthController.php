<?php

namespace App\Http\Controllers\Api\V1Dot1;

use App\EmailVerification;
use App\Http\Controllers\Controller;
use App\Mail\ConfirmAppEmail;
use App\Services\BouncerService;
use App\Services\EmailService;
use App\Services\NotificationAppGatewayService;
use App\User;
use App\Util\Lexer\RestrictedNames;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use Mail;

class AuthController extends Controller
{
    protected $fractal;

    public function __construct()
    {
        $this->fractal = new Fractal\Manager;
        $this->fractal->setSerializer(new ArraySerializer);
    }

    public function json($res, $code = 200, $headers = [])
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    public function error($msg, $code = 400, $extra = [], $headers = [])
    {
        $res = [
            'msg' => $msg,
            'code' => $code,
        ];

        return response()->json(array_merge($res, $extra), $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    public function inAppRegistrationPreFlightCheck(Request $request)
    {
        return [
            'open' => (bool) config_cache('pixelfed.open_registration'),
            'iara' => (bool) config_cache('pixelfed.allow_app_registration'),
        ];
    }

    public function inAppRegistration(Request $request)
    {
        abort_if($request->user(), 404);
        abort_unless((bool) config_cache('pixelfed.open_registration'), 404);
        abort_unless((bool) config_cache('pixelfed.allow_app_registration'), 404);
        abort_unless($request->hasHeader('X-PIXELFED-APP'), 403);
        if (config('pixelfed.bouncer.cloud_ips.ban_signups')) {
            abort_if(BouncerService::checkIp($request->ip()), 404);
        }

        $rl = RateLimiter::attempt('pf:apiv1.1:iar:'.$request->ip(), config('pixelfed.app_registration_rate_limit_attempts', 3), function () {}, config('pixelfed.app_registration_rate_limit_decay', 1800));
        abort_if(! $rl, 400, 'Too many requests');

        $this->validate($request, [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users',
                function ($attribute, $value, $fail) {
                    $banned = EmailService::isBanned($value);
                    if ($banned) {
                        return $fail('Email is invalid.');
                    }
                },
            ],
            'username' => [
                'required',
                'min:2',
                'max:30',
                'unique:users',
                function ($attribute, $value, $fail) {
                    $dash = substr_count($value, '-');
                    $underscore = substr_count($value, '_');
                    $period = substr_count($value, '.');

                    if (ends_with($value, ['.php', '.js', '.css'])) {
                        return $fail('Username is invalid.');
                    }

                    if (($dash + $underscore + $period) > 1) {
                        return $fail('Username is invalid. Can only contain one dash (-), period (.) or underscore (_).');
                    }

                    if (! ctype_alnum($value[0])) {
                        return $fail('Username is invalid. Must start with a letter or number.');
                    }

                    if (! ctype_alnum($value[strlen($value) - 1])) {
                        return $fail('Username is invalid. Must end with a letter or number.');
                    }

                    $val = str_replace(['_', '.', '-'], '', $value);
                    if (! ctype_alnum($val)) {
                        return $fail('Username is invalid. Username must be alpha-numeric and may contain dashes (-), periods (.) and underscores (_).');
                    }

                    $restricted = RestrictedNames::get();
                    if (in_array(strtolower($value), array_map('strtolower', $restricted))) {
                        return $fail('Username cannot be used.');
                    }
                },
            ],
            'password' => 'required|string|min:8',
        ]);

        $email = $request->input('email');
        $username = $request->input('username');
        $password = $request->input('password');

        if (config('database.default') == 'pgsql') {
            $username = strtolower($username);
            $email = strtolower($email);
        }

        $user = new User;
        $user->name = $username;
        $user->username = $username;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->register_source = 'app';
        $user->app_register_ip = $request->ip();
        $user->app_register_token = Str::random(40);
        $user->save();

        $rtoken = Str::random(64);

        $verify = new EmailVerification;
        $verify->user_id = $user->id;
        $verify->email = $user->email;
        $verify->user_token = $user->app_register_token;
        $verify->random_token = $rtoken;
        $verify->save();

        $params = http_build_query([
            'ut' => $user->app_register_token,
            'rt' => $rtoken,
            'ea' => base64_encode($user->email),
        ]);
        $appUrl = url('/api/v1.1/auth/iarer?'.$params);

        Mail::to($user->email)->send(new ConfirmAppEmail($verify, $appUrl));

        return response()->json([
            'success' => true,
        ]);
    }

    public function inAppRegistrationEmailRedirect(Request $request)
    {
        $this->validate($request, [
            'ut' => 'required',
            'rt' => 'required',
            'ea' => 'required',
        ]);
        $ut = $request->input('ut');
        $rt = $request->input('rt');
        $ea = $request->input('ea');
        $params = http_build_query([
            'ut' => $ut,
            'rt' => $rt,
            'domain' => config('pixelfed.domain.app'),
            'ea' => $ea,
        ]);
        $url = 'pixelfed://confirm-account/'.$ut.'?'.$params;

        return redirect()->away($url);
    }

    public function inAppRegistrationConfirm(Request $request)
    {
        abort_if($request->user(), 404);
        abort_unless((bool) config_cache('pixelfed.open_registration'), 404);
        abort_unless((bool) config_cache('pixelfed.allow_app_registration'), 404);
        abort_unless($request->hasHeader('X-PIXELFED-APP'), 403);
        if (config('pixelfed.bouncer.cloud_ips.ban_signups')) {
            abort_if(BouncerService::checkIp($request->ip()), 404);
        }

        $request->validate([
            'user_token' => 'required',
            'random_token' => 'required',
            'email' => 'required',
        ]);

        $verify = EmailVerification::whereEmail($request->input('email'))
            ->whereUserToken($request->input('user_token'))
            ->whereRandomToken($request->input('random_token'))
            ->first();

        if (! $verify) {
            return response()->json(['error' => 'Invalid tokens'], 403);
        }

        if ($verify->created_at->lt(now()->subHours(24))) {
            $verify->delete();

            return response()->json(['error' => 'Invalid tokens'], 403);
        }

        $user = User::findOrFail($verify->user_id);
        $user->email_verified_at = now();
        $user->last_active_at = now();
        $user->save();

        $token = $user->createToken('Pixelfed', ['read', 'write', 'follow', 'push']);

        return response()->json([
            'access_token' => $token->accessToken,
        ]);
    }


}