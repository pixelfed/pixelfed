<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\PasswordResetResponse;
use App\Http\Responses\RegisterResponse;
use App\Models\User;
use App\Services\BouncerService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\PasswordResetResponse as PasswordResetResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;
use RuntimeException;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * Bind the custom Fortify response contracts to their concrete
     * implementations so successful login, registration, and password-reset
     * responses redirect to `/i/web` and preserve the legacy audit logging.
     *
     * Requirements: 5.1, 5.2, 5.3.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
        $this->app->singleton(PasswordResetResponseContract::class, PasswordResetResponse::class);
    }

    /**
     * Bootstrap any application services.
     *
     * Wiring is added over subsequent tasks:
     * - Fortify::authenticateUsing() closure  -> task 2.1
     * - Fortify view callbacks                 -> task 4.1
     * - Fortify::createUsersUsing()            -> task 5.3
     * - Fortify::resetUserPasswordsUsing()     -> task 6.3
     * - Fortify::updateUserPasswordsUsing()    -> task 6.3
     *
     * Account-enumeration protection on the password-reset flow relies on
     * Fortify's constant-response password-reset-link behavior (the same
     * generic status is returned whether or not the email exists); no
     * artificial timing jitter is applied. Broker-failure responses (Req 7.6,
     * 7.7) use Fortify's defaults, which already match the legacy behavior.
     */
    public function boot(): void
    {
        $this->guardFortifyConfiguration();

        $this->registerFortifyViews();

        $this->registerAuthenticationClosure();

        $this->registerLoginRateLimiter();

        $this->registerFortifyActions();
    }

    /**
     * Bind the Fortify action classes that drive account creation and password
     * changes onto their Fortify pipelines.
     *
     * The registration (create), password-reset, and password-update actions
     * are bound here. Broker-failure handling for the reset flow is left to
     * Fortify's default FailedPasswordResetResponse and
     * FailedPasswordResetLinkRequestResponse, which already reproduce the
     * legacy behavior (HTML: redirect back with an `email` error; JSON: raise
     * ValidationException) required by Requirements 7.6 and 7.7.
     *
     * Requirements: 6.8, 7.6, 7.7, 8.1.
     */
    protected function registerFortifyActions(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
    }

    /**
     * Point Fortify's view callbacks at the existing Pixelfed Blade templates so
     * the login, registration, and password-reset screens render unchanged.
     *
     * The reset-password view is populated with the `token` (from the route) and
     * `email` (from the request), matching the legacy
     * ResetPasswordController::showResetForm behavior. Fortify accepts `email`
     * as the login identifier via the configured `fortify.username`, so no field
     * rename is required.
     *
     * Requirements: 2.1, 2.4, 2.5, 2.6, 2.7, 2.8.
     */
    protected function registerFortifyViews(): void
    {
        Fortify::loginView(fn (): View => view('auth.login'));

        Fortify::registerView(fn (): RedirectResponse|View => $this->resolveRegisterView());

        Fortify::requestPasswordResetLinkView(fn (): View => view('auth.passwords.email'));

        Fortify::resetPasswordView(fn (Request $request): View => view('auth.passwords.reset', [
            'token' => $request->route('token'),
            'email' => $request->email,
        ]));

        Fortify::confirmPasswordView(fn (): View => view('auth.sudo'));
    }

    /**
     * Resolve the registration view response, reproducing the legacy
     * RegisterController::showRegistrationForm gating.
     *
     * When open registration is enabled, the bouncer IP ban and enforced
     * max-users limit are applied before rendering the register view. When
     * open registration is disabled, curated-registration fallback redirects
     * to the sign-up flow if configured, otherwise the page returns 404.
     *
     * Requirements: 2.1.
     */
    protected function resolveRegisterView(): RedirectResponse|View
    {
        if ((bool) config_cache('pixelfed.open_registration')) {
            if (config('pixelfed.bouncer.cloud_ips.ban_signups')) {
                abort_if(BouncerService::checkIp(request()->ip()), 404);
            }

            if (config('pixelfed.enforce_max_users')) {
                $limit = config('pixelfed.max_users');
                $count = User::where(function ($query) {
                    return $query->whereNull('status')->orWhereNotIn('status', ['deleted', 'delete']);
                })->count();

                if ($limit <= $count) {
                    return redirect(route('help.instance-max-users-limit'));
                }
            }

            return view('auth.register');
        }

        if ((bool) config_cache('instance.curated_registration.enabled') && config('instance.curated_registration.state.fallback_on_closed_reg')) {
            return redirect('/auth/sign_up');
        }

        abort(404);
    }

    /**
     * Register the Fortify `login` rate limiter, reproducing the legacy
     * LoginController throttle (maxAttempts=5, decayMinutes=60): 5 attempts per
     * 60-minute window keyed by the lowercased login username and request IP.
     * Exceeding the limit yields an automatic HTTP 429 response.
     *
     * Requirements: 4.5.
     */
    protected function registerLoginRateLimiter(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            $key = Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip();

            return Limit::perMinutes(60, 5)->by($key);
        });
    }

    /**
     * Register the Fortify authentication closure that reproduces the legacy
     * LoginController behavior (bouncer IP ban, hCaptcha triggers, and the
     * bcrypt credential check against the existing users table).
     *
     * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 3.8, 4.1, 4.2.
     */
    protected function registerAuthenticationClosure(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            if (config('pixelfed.bouncer.cloud_ips.ban_logins')) {
                abort_if(BouncerService::checkIp($request->ip()), 404);
            }

            $email = (string) $request->input('email', '');
            $password = (string) $request->input('password', '');

            if ($this->loginRequiresCaptcha($request) && ! $this->passesCaptcha($request)) {
                $this->incrementLoginAttempts($request);

                throw ValidationException::withMessages([
                    Fortify::username() => [trans('auth.failed')],
                ]);
            }

            if ($email === '' || $password === '' || mb_strlen($email) > 255 || mb_strlen($password) > 255) {
                return null;
            }

            $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->first();

            if (! $user || $user->status === 'deleted') {
                return null;
            }

            if (Hash::check($password, $user->password)) {
                return $user;
            }

            return null;
        });
    }

    /**
     * Determine whether captcha validation is required for the login request,
     * mirroring the legacy LoginController::validateLogin trigger logic.
     */
    protected function loginRequiresCaptcha(Request $request): bool
    {
        if ((bool) config_cache('captcha.enabled') && (bool) config_cache('captcha.active.login')) {
            return true;
        }

        return (bool) config_cache('captcha.triggers.login.enabled')
            && $request->session()->has('login_attempts')
            && $request->session()->get('login_attempts') >= config('captcha.triggers.login.attempts');
    }

    /**
     * Validate the h-captcha-response field using the same rules as the legacy
     * LoginController.
     */
    protected function passesCaptcha(Request $request): bool
    {
        $validator = validator($request->all(), [
            'h-captcha-response' => 'required|filled|captcha|min:5',
        ]);

        return $validator->passes();
    }

    /**
     * Increment the login_attempts session counter, matching the legacy
     * LoginController::sendFailedLoginResponse behavior (+1, or set to 1 when
     * the counter is absent).
     */
    protected function incrementLoginAttempts(Request $request): void
    {
        if ($request->session()->has('login_attempts')) {
            $request->session()->put('login_attempts', $request->session()->get('login_attempts') + 1);
        } else {
            $request->session()->put('login_attempts', 1);
        }
    }

    /**
     * Ensure the core Fortify configuration resolves before any Fortify
     * authentication wiring takes effect (Requirement 1.9).
     *
     * @throws RuntimeException when the guard, password broker, or username
     *                          configuration is absent or resolves to null.
     */
    protected function guardFortifyConfiguration(): void
    {
        $required = [
            'fortify.guard' => config('fortify.guard'),
            'fortify.passwords' => config('fortify.passwords'),
            'fortify.username' => config('fortify.username'),
        ];

        foreach ($required as $key => $value) {
            if ($value === null || $value === '') {
                throw new RuntimeException(
                    "Invalid Fortify configuration: [{$key}] is missing or null. ".
                    'Verify config/fortify.php defines a guard, password broker, and username field.'
                );
            }
        }
    }
}
