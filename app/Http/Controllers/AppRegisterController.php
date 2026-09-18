<?php

namespace App\Http\Controllers;

use App\Auth\AppRegisterTokenFactory;
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
use Laravel\Passport\Passport;
use Laravel\Passport\RefreshToken;
use League\OAuth2\Server\Exception\OAuthServerException;
use Purify;

class AppRegisterController extends Controller
{
    private const int VERIFY_CODE_MAX_ATTEMPTS = 10;

    private const int VERIFY_CODE_TTL_SECONDS = 3600;

    private const int RESEND_MAX_USES = 5;

    /**
     * Where the web steps send the browser when no redirect_uri is given.
     * Keeps the original app working unchanged.
     */
    private const string LEGACY_REDIRECT_URI = 'pixelfed://verifyEmail';

    private const array DEFAULT_SCOPES = ['read', 'write', 'follow', 'push'];

    private const array BLOCKED_REDIRECT_SCHEMES = [
        'http',
        'https',
        'javascript',
        'data',
        'file',
        'ftp',
        'blob',
        'vbscript',
        'about',
    ];

    public function index(Request $request): RedirectResponse|View
    {
        abort_unless(config('auth.in_app_registration'), 404);
        $open = (bool) config_cache('pixelfed.open_registration');
        if (! $open || $request->user()) {
            return redirect('/');
        }

        $redirectUri = $this->resolveRedirectUri($request);

        return view('auth.iar', [
            'redirectUri' => $redirectUri,
            'resendUrl' => $this->resendUrl($redirectUri),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(config('auth.in_app_registration'), 404);
        $open = (bool) config_cache('pixelfed.open_registration');
        if (! $open || $request->user()) {
            return redirect('/');
        }

        $redirectUri = $this->resolveRedirectUri($request);

        $rules = [
            'email' => 'required|email:rfc,dns,spoof,strict|unique:users,email|unique:app_registers,email',
        ];

        if (app('captcha.manager')->activeOn('register')) {
            $rules[app('captcha.manager')->active()->responseField()] = 'required|captcha_verify';
        }

        $this->validate($request, $rules);

        $email = strtolower($request->input('email'));
        $code = $this->generateCode();

        DB::beginTransaction();

        $exists = AppRegister::whereEmail($email)->count();

        if ($exists) {
            DB::rollBack();

            return $this->appRedirect($redirectUri, [
                'status' => 'error',
                'message' => 'Too many attempts, please try again later.',
            ]);
        }

        AppRegister::create([
            'email' => $email,
            'verify_code' => $code,
            'uses' => 1,
            'email_delivered_at' => now(),
        ]);

        try {
            Mail::to($email)->send(new InAppRegisterEmailVerify($code));
        } catch (\Exception) {
            DB::rollBack();

            return $this->appRedirect($redirectUri, [
                'status' => 'error',
                'message' => 'Failed to send verification code',
            ]);
        }

        DB::commit();

        return $this->appRedirect($redirectUri, [
            'status' => 'success',
            'email' => $email,
            'expires_in' => self::VERIFY_CODE_TTL_SECONDS,
        ]);
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

        return view('auth.iar-resend', [
            'redirectUri' => $this->resolveRedirectUri($request),
        ]);
    }

    public function resendVerificationStore(Request $request): RedirectResponse
    {
        abort_unless(config('auth.in_app_registration'), 404);
        $open = (bool) config_cache('pixelfed.open_registration');
        if (! $open || $request->user()) {
            return redirect('/');
        }

        $redirectUri = $this->resolveRedirectUri($request);

        $rules = [
            'email' => 'required|email:rfc,dns,spoof,strict|unique:users,email|exists:app_registers,email',
        ];

        if (app('captcha.manager')->activeOn('register')) {
            $rules[app('captcha.manager')->active()->responseField()] = 'required|captcha_verify';
        }

        $this->validate($request, $rules);

        $email = strtolower($request->input('email'));
        $code = $this->generateCode();

        DB::beginTransaction();

        $exists = AppRegister::whereEmail($email)->first();

        if (! $exists || $exists->uses > self::RESEND_MAX_USES) {
            DB::rollBack();

            return $this->appRedirect($redirectUri, [
                'status' => 'error',
                'message' => ! $exists
                    ? 'Email not found'
                    : 'Too many attempts have been made, please contact the admins.',
            ]);
        }

        $exists->update([
            'verify_code' => $code,
            'uses' => ($exists->uses + 1),
            'failed_attempts' => 0,
            'email_delivered_at' => now(),
        ]);

        try {
            Mail::to($email)->send(new InAppRegisterEmailVerify($code));
        } catch (\Exception) {
            DB::rollBack();

            return $this->appRedirect($redirectUri, [
                'status' => 'error',
                'message' => 'Failed to send verification code',
            ]);
        }

        DB::commit();

        return $this->appRedirect($redirectUri, [
            'status' => 'success',
            'email' => $email,
            'expires_in' => self::VERIFY_CODE_TTL_SECONDS,
        ]);
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
            'client_id' => 'nullable|string|max:80|required_with:client_secret',
            'client_secret' => 'nullable|string|max:255|required_with:client_id',
            'scope' => 'nullable|string|max:255',
        ]);

        $email = strtolower($request->input('email'));
        $code = (string) $request->input('verify_code');
        $username = $request->input('username');
        $name = $request->input('name');
        $password = $request->input('password');
        $clientId = $request->input('client_id');
        $clientSecret = $request->input('client_secret');

        $tokenFactory = app(AppRegisterTokenFactory::class);
        $scopes = null;

        if ($clientId) {
            $scopes = $this->resolveScopes($request->input('scope'));

            if ($scopes === null) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'invalid_scope',
                    'message' => 'Invalid scope.',
                ], 422);
            }

            if (! $tokenFactory->validateClient((string) $clientId, (string) $clientSecret)) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'invalid_client',
                    'message' => 'Invalid client credentials.',
                ], 401);
            }
        }

        $result = $this->checkVerificationCode($email, $code);

        if ($result['locked']) {
            return response()->json([
                'status' => 'error',
                'code' => 'locked',
                'message' => 'Too many verification attempts. Please request a new code.',
            ], 429);
        }

        if (! $result['valid']) {
            return response()->json([
                'status' => 'error',
                'code' => 'invalid_code',
                'message' => 'Invalid or expired verification code.',
            ]);
        }

        $user = User::create([
            'name' => $name ? Purify::clean($name) : null,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'app_register_ip' => request()->ip(),
            'register_source' => 'app',
            'email_verified_at' => now(),
        ]);

        $user->refresh();

        AppRegister::whereEmail($email)->delete();

        if (! $clientId) {
            return $this->legacyOnboardingResponse($user);
        }

        try {
            $tokens = $tokenFactory->issue($user, (string) $clientId, (string) $clientSecret, $scopes);
        } catch (OAuthServerException) {
            return response()->json([
                'status' => 'error',
                'code' => 'account_created_token_failed',
                'message' => 'Your account was created but we could not sign you in automatically. Please sign in with your email and password.',
            ]);
        }

        return response()->json([
            'status' => 'success',
            'domain' => config('pixelfed.domain.app'),
            'token_type' => 'Bearer',
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? null,
            'expires_in' => $tokens['expires_in'],
            'scope' => $scopes,
            'client_id' => (string) $clientId,
            'user' => [
                'pid' => (string) $user->profile_id,
                'username' => $user->username,
            ],
            'account' => AccountService::get($user->profile_id, true),
        ]);
    }

    /**
     * Original personal-access-token path, kept byte-for-byte in behaviour
     * for the previous app. Note the refresh_token here is a raw row id and
     * is not usable with the refresh_token grant.
     */
    protected function legacyOnboardingResponse(User $user): JsonResponse
    {
        $token = $user->createToken('Pixelfed App', self::DEFAULT_SCOPES);
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

        return response()->json([
            'status' => 'success',
            'token_type' => 'Bearer',
            'domain' => config('pixelfed.domain.app'),
            'expires_in' => $expiresIn,
            'access_token' => $token->accessToken,
            'refresh_token' => $refreshToken->id,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => self::DEFAULT_SCOPES,
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

    protected function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Space or plus separated scope string from the app. Returns null when
     * any requested scope is unknown to Passport. Empty means defaults.
     *
     * @return string[]|null
     */
    protected function resolveScopes(?string $scope): ?array
    {
        $scopes = collect(explode(' ', str_replace('+', ' ', trim((string) $scope))))
            ->map(fn ($s): string => trim($s))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (! count($scopes)) {
            return self::DEFAULT_SCOPES;
        }

        foreach ($scopes as $s) {
            if ($s === '*' || ! Passport::hasScope($s)) {
                return null;
            }
        }

        return $scopes;
    }

    /**
     * Validates the optional redirect_uri the app passes to the web steps.
     * Only custom schemes on the configured allowlist are accepted, so this
     * can never become an open redirect to a web origin. Query and fragment
     * are stripped since we append our own params.
     */
    protected function resolveRedirectUri(Request $request): string
    {
        $uri = $request->input('redirect_uri');

        if (! is_string($uri) || trim($uri) === '') {
            return self::LEGACY_REDIRECT_URI;
        }

        $uri = trim($uri);

        abort_if(
            strlen($uri) > 512 || preg_match('/[\s\x00-\x1F\x7F]/', $uri),
            400,
            'Invalid redirect_uri.'
        );

        $scheme = strtolower((string) parse_url($uri, PHP_URL_SCHEME));

        abort_if(
            $scheme === '' ||
                in_array($scheme, self::BLOCKED_REDIRECT_SCHEMES, true) ||
                ! in_array($scheme, $this->allowedRedirectSchemes(), true),
            400,
            'Invalid redirect_uri.'
        );

        return preg_replace('/[?#].*$/', '', $uri);
    }

    /**
     * @return string[]
     */
    protected function allowedRedirectSchemes(): array
    {
        return collect(explode(',', (string) config('auth.in_app_registration_redirect_schemes', 'pixelfed')))
            ->map(fn ($s): string => strtolower(trim($s)))
            ->filter()
            ->values()
            ->all();
    }

    protected function resendUrl(string $redirectUri): string
    {
        if ($redirectUri === self::LEGACY_REDIRECT_URI) {
            return '/i/app-email-resend';
        }

        return '/i/app-email-resend?'.http_build_query(['redirect_uri' => $redirectUri]);
    }

    protected function appRedirect(string $redirectUri, array $params): RedirectResponse
    {
        return redirect()->away($redirectUri.'?'.http_build_query($params));
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
