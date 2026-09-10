<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AccountLog;
use App\Models\User;
use App\Services\BouncerService;
use App\Services\EmailVerificationService;
use App\Services\PendingLoginService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Passport;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | Password is checked first, without creating a session. Users with 2FA
    | enabled, or an unverified email when enforcement is on, are held in a
    | pending state (see PendingLoginService) and only get a real session once
    | every step passes. Because nothing consumes url.intended until the final
    | sendLoginResponse, OAuth authorize URLs (and any other guarded deep link)
    | survive the whole flow.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/i/web';

    protected $maxAttempts = 5;

    protected $decayMinutes = 60;

    public function __construct()
    {
        $this->middleware('guest')->except(['logout', 'confirmEmail']);
    }

    public function showLoginForm(Request $request): View|RedirectResponse
    {
        $this->bounce($request);

        $step = $request->query('step');
        $pendingStep = PendingLoginService::step($request);

        if (! in_array($step, [PendingLoginService::STEP_2FA, PendingLoginService::STEP_VERIFY], true)) {
            return view('auth.login', [
                'step' => 'credentials',
                'pendingUser' => null,
                'returnTo' => $this->intendedClientName($request),
            ]);
        }

        if (! $pendingStep) {
            return redirect()->route('login');
        }

        if ($pendingStep !== $step) {
            return redirect()->route('login', ['step' => $pendingStep]);
        }

        $user = PendingLoginService::user($request);

        if (! $user) {
            return redirect()->route('login');
        }

        return view('auth.login', [
            'step' => $step,
            'pendingUser' => $user,
            'returnTo' => $this->intendedClientName($request),
            'mode' => $request->query('mode') === 'backup' ? 'backup' : 'totp',
            'changeEmail' => $request->boolean('change'),
        ]);
    }

    /**
     * Handle a login request. Overrides the trait so credentials are validated
     * without logging the user in.
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        $credentials = $this->credentials($request);

        if (! $this->guard()->validate($credentials)) {
            $this->incrementLoginAttempts($request);
            $this->sendFailedLoginResponse($request);
        }

        $user = $this->guard()->getLastAttempted();
        $provider = $this->guard()->getProvider();

        if (method_exists($provider, 'rehashPasswordIfRequired')) {
            $provider->rehashPasswordIfRequired($user, $credentials);
        }

        $remember = $request->boolean('remember');

        if ((bool) $user->{'2fa_enabled'}) {
            PendingLoginService::start($request, $user, $remember, PendingLoginService::STEP_2FA);

            return redirect()->route('login', ['step' => PendingLoginService::STEP_2FA]);
        }

        return $this->completeLogin($request, $user, $remember);
    }

    public function verifyTwoFactor(Request $request): Response
    {
        $this->bounce($request);

        $request->validate([
            'code' => 'required|string|max:32',
        ]);

        $user = $this->pendingUser($request, PendingLoginService::STEP_2FA);

        if (! $user) {
            return $this->redirectToPendingStep($request);
        }

        if (PendingLoginService::verifyCode($user, (string) $request->input('code'))) {
            return $this->completeLogin($request, $user, PendingLoginService::remember($request));
        }

        $this->log($request, $user, 'auth.2fa.failed', '2FA verification failed');

        if (PendingLoginService::recordFailure($request)) {
            return redirect()->route('login')->withErrors([
                'login' => __('Too many invalid codes. Sign in again to retry.'),
            ]);
        }

        $remaining = PendingLoginService::attemptsRemaining($request);

        $params = ['step' => PendingLoginService::STEP_2FA];

        if ($request->input('mode') === 'backup') {
            $params['mode'] = 'backup';
        }

        return redirect()->route('login', $params)->withErrors([
            'code' => __('Invalid code.').' '.trans_choice('{1} :count attempt left|[2,*] :count attempts left', $remaining, ['count' => $remaining]),
        ]);
    }

    public function cancelPendingLogin(Request $request): RedirectResponse
    {
        PendingLoginService::clear($request);

        return redirect()->route('login');
    }

    public function continueAfterVerification(Request $request): Response
    {
        $user = $this->pendingUser($request, PendingLoginService::STEP_VERIFY);

        if (! $user) {
            return $this->redirectToPendingStep($request);
        }

        if ($this->requiresEmailVerification($user)) {
            return redirect()->route('login', ['step' => PendingLoginService::STEP_VERIFY])->withErrors([
                'verify' => __('Your email is not verified yet. Open the link we sent, or resend it below.'),
            ]);
        }

        return $this->completeLogin($request, $user, PendingLoginService::remember($request));
    }

    public function resendVerification(Request $request): Response
    {
        $user = $this->pendingUser($request, PendingLoginService::STEP_VERIFY);

        if (! $user) {
            return $this->redirectToPendingStep($request);
        }

        if (! $this->requiresEmailVerification($user)) {
            return $this->completeLogin($request, $user, PendingLoginService::remember($request));
        }

        if (! EmailVerificationService::send($user)) {
            return redirect()->route('login', ['step' => PendingLoginService::STEP_VERIFY])->withErrors([
                'verify' => __('A verification email was sent a moment ago. Check your inbox, then try again in a minute.'),
            ]);
        }

        return redirect()->route('login', ['step' => PendingLoginService::STEP_VERIFY])
            ->with('status', __('Verification email sent to').' '.$user->email);
    }

    public function updatePendingEmail(Request $request): Response
    {
        $user = $this->pendingUser($request, PendingLoginService::STEP_VERIFY);

        if (! $user) {
            return $this->redirectToPendingStep($request);
        }

        if (! $this->requiresEmailVerification($user)) {
            return $this->completeLogin($request, $user, PendingLoginService::remember($request));
        }

        $request->validate([
            'email' => 'required|string|email|max:255|unique:users,email',
        ]);

        $user->email = $request->input('email');
        $user->email_verified_at = null;
        $user->save();

        PendingLoginService::start($request, $user, PendingLoginService::remember($request), PendingLoginService::STEP_VERIFY);

        $this->log($request, $user, 'account.email.changed', 'Email address changed before verification');

        EmailVerificationService::send($user);

        return redirect()->route('login', ['step' => PendingLoginService::STEP_VERIFY])
            ->with('status', __('Verification email sent to').' '.$user->email);
    }

    /**
     * Token-only confirmation, safe for guests. Completes the pending login
     * when the confirming browser is the one waiting on this address.
     */
    public function confirmEmail(Request $request, string $userToken, string $randomToken): Response
    {
        $user = EmailVerificationService::confirm($userToken, $randomToken);

        if (! $user) {
            if ($request->user() !== null) {
                return redirect($this->redirectPath());
            }

            return redirect()->route('login')->withErrors([
                'login' => __('That verification link is invalid or has expired. Sign in to request a new one.'),
            ]);
        }

        $this->log($request, $user, 'account.email.verified', 'Email address verified');

        if ($request->user() !== null) {
            return redirect($this->redirectPath());
        }

        $pending = PendingLoginService::user($request);

        if ($pending && (int) $pending->id === (int) $user->id) {
            if (PendingLoginService::step($request) === PendingLoginService::STEP_VERIFY) {
                return $this->completeLogin($request, $user, PendingLoginService::remember($request));
            }

            return redirect()->route('login', ['step' => PendingLoginService::step($request)])
                ->with('status', __('Email verified.'));
        }

        return redirect()->route('login')->with('status', __('Email verified. Sign in to continue.'));
    }

    /**
     * Final gate. Either parks the user on the verify step or creates the
     * session and hands off to sendLoginResponse (which consumes url.intended).
     */
    protected function completeLogin(Request $request, User $user, bool $remember): Response
    {
        if ($this->requiresEmailVerification($user)) {
            PendingLoginService::start($request, $user, $remember, PendingLoginService::STEP_VERIFY);
            EmailVerificationService::send($user, EmailVerificationService::AUTO_SEND_COOLDOWN_SECONDS);

            return redirect()->route('login', ['step' => PendingLoginService::STEP_VERIFY]);
        }

        $this->guard()->login($user, $remember);

        $response = $this->sendLoginResponse($request);

        PendingLoginService::clear($request);

        return $response;
    }

    protected function requiresEmailVerification(User $user): bool
    {
        return (bool) config('pixelfed.enforce_email_verification') && is_null($user->email_verified_at);
    }

    protected function pendingUser(Request $request, string $step): ?User
    {
        if (PendingLoginService::step($request) !== $step) {
            return null;
        }

        return PendingLoginService::user($request);
    }

    protected function redirectToPendingStep(Request $request): RedirectResponse
    {
        if ($step = PendingLoginService::step($request)) {
            return redirect()->route('login', ['step' => $step]);
        }

        return redirect()->route('login')->withErrors([
            'login' => __('Your sign-in session expired. Please sign in again.'),
        ]);
    }

    /**
     * Name of the OAuth client the user will be returned to, when the login
     * was triggered by /oauth/authorize.
     */
    protected function intendedClientName(Request $request): ?string
    {
        $intended = $request->session()->get('url.intended');

        if (! is_string($intended) || parse_url($intended, PHP_URL_PATH) !== '/oauth/authorize') {
            return null;
        }

        parse_str((string) parse_url($intended, PHP_URL_QUERY), $query);

        if (empty($query['client_id']) || ! is_string($query['client_id'])) {
            return null;
        }

        try {
            $name = Passport::client()->newQuery()->whereKey($query['client_id'])->value('name');
        } catch (\Throwable) {
            return null;
        }

        return is_string($name) && $name !== '' ? $name : null;
    }

    protected function bounce(Request $request): void
    {
        if (config('pixelfed.bouncer.cloud_ips.ban_logins')) {
            abort_if(BouncerService::checkIp($request->ip()), 404);
        }
    }

    /**
     * The 2FA and verify routes carry no email input, so key the throttle on
     * the pending login's email instead.
     */
    protected function throttleKey(Request $request): string
    {
        $email = $request->input($this->username()) ?: PendingLoginService::email($request);

        return Str::transliterate(Str::lower((string) $email).'|'.$request->ip());
    }

    /**
     * Validate the user login request.
     *
     * @param  Request  $request
     */
    public function validateLogin($request): void
    {
        $this->bounce($request);

        $rules = [
            $this->username() => 'required|email',
            'password' => 'required|string|min:6',
        ];
        $messages = [];

        if (
            (bool) config_cache('captcha.enabled') &&
            (bool) config_cache('captcha.active.login') ||
            (
                (bool) config_cache('captcha.triggers.login.enabled') &&
                request()->session()->has('login_attempts') &&
                request()->session()->get('login_attempts') >= config('captcha.triggers.login.attempts')
            )
        ) {
            $rules['h-captcha-response'] = 'required|filled|captcha|min:5';
            $messages['h-captcha-response.required'] = 'The captcha must be filled';
        }
        $request->validate($rules, $messages);
    }

    /**
     * The user has been authenticated.
     *
     * @param  Request  $request
     * @param  mixed  $user
     */
    protected function authenticated($request, $user): void
    {
        if ($user->status == 'deleted') {
            return;
        }

        $this->log($request, $user, 'auth.login', 'Account Login');
    }

    /**
     * Get the failed login response instance.
     *
     * @throws ValidationException
     */
    protected function sendFailedLoginResponse(Request $request): void
    {
        if (config('captcha.triggers.login.enabled')) {
            if ($request->session()->has('login_attempts')) {
                $ct = $request->session()->get('login_attempts');
                $request->session()->put('login_attempts', $ct + 1);
            } else {
                $request->session()->put('login_attempts', 1);
            }
        }

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }

    protected function log(Request $request, User $user, string $action, string $message): void
    {
        $log = new AccountLog;
        $log->user_id = $user->id;
        $log->item_id = $user->id;
        $log->item_type = User::class;
        $log->action = $action;
        $log->message = $message;
        $log->link = null;
        $log->ip_address = $request->ip();
        $log->user_agent = $request->userAgent();
        $log->save();
    }
}
