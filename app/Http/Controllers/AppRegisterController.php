<?php

namespace App\Http\Controllers;

use App\Mail\InAppRegisterEmailVerify;
use App\Models\AppRegister;
use App\Models\User;
use App\Rules\ValidUsername;
use App\Services\AccountService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Passport\RefreshToken;
use Purify;

class AppRegisterController extends Controller
{
    private const VERIFY_CODE_MAX_ATTEMPTS = 10;

    private const VERIFY_CODE_TTL_SECONDS = 3600;

    public function index(Request $request): RedirectResponse|View
    {
        abort_unless(config('auth.in_app_registration'), 404);
        $open = (bool) config_cache('pixelfed.open_registration');
        if (! $open || $request->user()) {
            return redirect('/');
        }

        return view('auth.iar');
    }

    public function store(Request $request): RedirectResponse
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

    public function verifyCode(Request $request): JsonResponse|RedirectResponse
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
        $code = (string) $request->input('verify_code');

        $result = $this->checkVerificationCode($email, $code);

        if ($result['locked']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Too many verification attempts. Please request a new code.',
            ], 429);
        }

        return response()->json([
            'status' => $result['valid'] ? 'success' : 'error',
        ]);
    }

    public function resendVerification(Request $request): RedirectResponse|View
    {
        abort_unless(config('auth.in_app_registration'), 404);
        $open = (bool) config_cache('pixelfed.open_registration');
        if (! $open || $request->user()) {
            return redirect('/');
        }

        return view('auth.iar-resend');
    }

    public function resendVerificationStore(Request $request): RedirectResponse
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
            $errorMessage = ! $exists
                ? 'Email not found'
                : 'Too many attempts have been made, please contact the admins.';

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
            'failed_attempts' => 0,
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

    public function onboarding(Request $request): JsonResponse|RedirectResponse
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

        $result = $this->checkVerificationCode($email, (string) $code);

        if ($result['locked']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Too many verification attempts. Please request a new code.',
            ], 429);
        }

        if (! $result['valid']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired verification code.',
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

        $user->refresh();
        $token = $user->createToken('Pixelfed App', ['read', 'write', 'follow', 'push']);
        $tokenModel = $token->token;
        $clientId = $tokenModel->client_id;
        $clientSecret = DB::table('oauth_clients')->where('id', $clientId)->value('secret');
        $refreshToken = RefreshToken::create([
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

    protected function validateUsernameRule(): array
    {
        return [
            'required',
            'min:2',
            'max:30',
            'unique:users',
            new ValidUsername,
        ];
    }

    protected function checkVerificationCode(string $email, string $code): array
    {
        return DB::transaction(function () use ($email, $code) {
            $registration = AppRegister::whereEmail($email)
                ->lockForUpdate()
                ->first();

            if (! $registration) {
                return [
                    'valid' => false,
                    'locked' => false,
                ];
            }

            if ((int) $registration->failed_attempts >= self::VERIFY_CODE_MAX_ATTEMPTS) {
                return [
                    'valid' => false,
                    'locked' => true,
                ];
            }

            $issuedAt = $registration->email_delivered_at
                ? Carbon::parse($registration->email_delivered_at)
                : null;

            if (
                ! $issuedAt ||
                $issuedAt->lte(now()->subSeconds(self::VERIFY_CODE_TTL_SECONDS))
            ) {
                return [
                    'valid' => false,
                    'locked' => false,
                ];
            }

            $storedCode = str_pad(
                (string) $registration->verify_code,
                6,
                '0',
                STR_PAD_LEFT
            );

            if (hash_equals($storedCode, (string) $code)) {
                return [
                    'valid' => true,
                    'locked' => false,
                ];
            }

            $registration->failed_attempts = min(
                self::VERIFY_CODE_MAX_ATTEMPTS,
                ((int) $registration->failed_attempts) + 1
            );

            $registration->save();

            return [
                'valid' => false,
                'locked' => $registration->failed_attempts >= self::VERIFY_CODE_MAX_ATTEMPTS,
            ];
        }, 3);
    }
}
