<?php

namespace App\Http\Controllers;

use App\Mail\InAppRegisterEmailVerify;
use App\Models\AppRegister;
use App\Services\AccountService;
use App\User;
use App\Util\Lexer\RestrictedNames;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Passport\RefreshTokenRepository;
use Purify;

class AppRegisterController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(config('auth.in_app_registration'), 404);
        $open = (bool) config_cache('pixelfed.open_registration');
        if (! $open || $request->user()) {
            return redirect('/');
        }

        return view('auth.iar');
    }

    public function store(Request $request)
    {
        abort_unless(config('auth.in_app_registration'), 404);
        $open = (bool) config_cache('pixelfed.open_registration');
        if (! $open || $request->user()) {
            return redirect('/');
        }

        $rules = [
            'email' => 'required|email:rfc,dns,spoof,strict|unique:users,email|unique:app_registers,email',
        ];

        if ((bool) config_cache('captcha.enabled') && (bool) config_cache('captcha.active.register')) {
            $rules['h-captcha-response'] = 'required|captcha';
        }

        $this->validate($request, $rules);

        $email = strtolower($request->input('email'));
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::beginTransaction();

        $exists = AppRegister::whereEmail($email)->count();

        if ($exists) {
            $errorParams = http_build_query([
                'status' => 'error',
                'message' => 'Too many attempts, please try again later.',
            ]);
            DB::rollBack();

            return redirect()->away("pixelfed://verifyEmail?{$errorParams}");
        }

        $registration = AppRegister::create([
            'email' => $email,
            'verify_code' => $code,
            'uses' => 1,
            'email_delivered_at' => now(),
        ]);

        try {
            Mail::to($email)->send(new InAppRegisterEmailVerify($code));
        } catch (\Exception $e) {
            DB::rollBack();
            $errorParams = http_build_query([
                'status' => 'error',
                'message' => 'Failed to send verification code',
            ]);

            return redirect()->away("pixelfed://verifyEmail?{$errorParams}");
        }

        DB::commit();

        $queryParams = http_build_query([
            'email' => $request->email,
            'expires_in' => 3600,
            'status' => 'success',
        ]);

        return redirect()->away("pixelfed://verifyEmail?{$queryParams}");
    }

    public function verifyCode(Request $request)
    {
        abort_unless(config('auth.in_app_registration'), 404);
        $open = (bool) config_cache('pixelfed.open_registration');
        if (! $open || $request->user()) {
            return redirect('/');
        }

        $this->validate($request, [
            'email' => 'required|email:rfc,dns,spoof,strict|unique:users,email|exists:app_registers,email',
            'verify_code' => ['required', 'digits:6', 'numeric'],
        ]);

        $email = strtolower($request->input('email'));
        $code = $request->input('verify_code');

        $exists = AppRegister::whereEmail($email)
            ->whereVerifyCode($code)
            ->where('created_at', '>', now()->subDays(90))
            ->exists();

        return response()->json([
            'status' => $exists ? 'success' : 'error',
        ]);
    }

    public function resendVerification(Request $request)
    {
        abort_unless(config('auth.in_app_registration'), 404);
        $open = (bool) config_cache('pixelfed.open_registration');
        if (! $open || $request->user()) {
            return redirect('/');
        }

        return view('auth.iar-resend');
    }

    public function resendVerificationStore(Request $request)
    {
        abort_unless(config('auth.in_app_registration'), 404);
        $open = (bool) config_cache('pixelfed.open_registration');
        if (! $open || $request->user()) {
            return redirect('/');
        }

        $rules = [
            'email' => 'required|email:rfc,dns,spoof,strict|unique:users,email|exists:app_registers,email',
        ];

        if ((bool) config_cache('captcha.enabled') && (bool) config_cache('captcha.active.register')) {
            $rules['h-captcha-response'] = 'required|captcha';
        }

        $this->validate($request, $rules);

        $email = strtolower($request->input('email'));
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::beginTransaction();

        $exists = AppRegister::whereEmail($email)->first();

        if (! $exists || $exists->uses > 5) {
            $errorMessage = $exists->uses > 5 ? 'Too many attempts have been made, please contact the admins.' : 'Email not found';
            $errorParams = http_build_query([
                'status' => 'error',
                'message' => $errorMessage,
            ]);
            DB::rollBack();

            return redirect()->away("pixelfed://verifyEmail?{$errorParams}");
        }

        $registration = $exists->update([
            'verify_code' => $code,
            'uses' => ($exists->uses + 1),
            'email_delivered_at' => now(),
        ]);

        try {
            Mail::to($email)->send(new InAppRegisterEmailVerify($code));
        } catch (\Exception $e) {
            DB::rollBack();
            $errorParams = http_build_query([
                'status' => 'error',
                'message' => 'Failed to send verification code',
            ]);

            return redirect()->away("pixelfed://verifyEmail?{$errorParams}");
        }

        DB::commit();

        $queryParams = http_build_query([
            'email' => $request->email,
            'expires_in' => 3600,
            'status' => 'success',
        ]);

        return redirect()->away("pixelfed://verifyEmail?{$queryParams}");
    }

    public function onboarding(Request $request)
    {
        abort_unless(config('auth.in_app_registration'), 404);
        $open = (bool) config_cache('pixelfed.open_registration');
        if (! $open || $request->user()) {
            return redirect('/');
        }

        $this->validate($request, [
            'email' => 'required|email:rfc,dns,spoof,strict|unique:users,email|exists:app_registers,email',
            'verify_code' => ['required', 'digits:6', 'numeric'],
            'username' => $this->validateUsernameRule(),
            'name' => 'nullable|string|max:'.config('pixelfed.max_name_length'),
            'password' => 'required|string|min:'.config('pixelfed.min_password_length'),
        ]);

        $email = strtolower($request->input('email'));
        $code = $request->input('verify_code');
        $username = $request->input('username');
        $name = $request->input('name');
        $password = $request->input('password');

        $exists = AppRegister::whereEmail($email)
            ->whereVerifyCode($code)
            ->where('created_at', '>', now()->subDays(90))
            ->exists();

        if (! $exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid verification code, please try again later.',
            ]);
        }

        $user = User::create([
            'name' => Purify::clean($name),
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'app_register_ip' => request()->ip(),
            'register_source' => 'app',
            'email_verified_at' => now(),
        ]);

        sleep(random_int(8, 10));
        $user = User::findOrFail($user->id);
        $token = $user->createToken('Pixelfed App', ['read', 'write', 'follow', 'push']);
        $tokenModel = $token->token;
        $clientId = $tokenModel->client_id;
        $clientSecret = DB::table('oauth_clients')->where('id', $clientId)->value('secret');
        $refreshTokenRepo = app(RefreshTokenRepository::class);
        $refreshToken = $refreshTokenRepo->create([
            'id' => Str::random(80),
            'access_token_id' => $tokenModel->id,
            'revoked' => false,
            'expires_at' => now()->addDays(config('instance.oauth.refresh_expiration', 400)),
        ]);

        $expiresAt = $tokenModel->expires_at ?? now()->addDays(config('instance.oauth.token_expiration', 356));
        $expiresIn = now()->diffInSeconds($expiresAt);
        AppRegister::whereEmail($email)->delete();

        return response()->json([
            'status' => 'success',
            'token_type' => 'Bearer',
            'domain' => config('pixelfed.domain.app'),
            'expires_in' => $expiresIn,
            'access_token' => $token->accessToken,
            'refresh_token' => $refreshToken->id,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => ['read', 'write', 'follow', 'push'],
            'user' => [
                'pid' => (string) $user->profile_id,
                'username' => $user->username,
            ],
            'account' => AccountService::get($user->profile_id, true),
        ]);
    }

    protected function validateUsernameRule()
    {
        return [
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

                if (! preg_match('/[a-zA-Z]/', $value)) {
                    return $fail('Username is invalid. Must contain at least one alphabetical character.');
                }

                $restricted = RestrictedNames::get();
                if (in_array(strtolower($value), array_map('strtolower', $restricted))) {
                    return $fail('Username cannot be used.');
                }
            },
        ];
    }
}
