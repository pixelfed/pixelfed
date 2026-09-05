<?php

use App\Http\Middleware\AccountInterstitial;
use App\Http\Middleware\Admin;
use App\Http\Middleware\Api\Admin as ApiAdmin;
use App\Http\Middleware\EmailVerificationCheck;
use App\Http\Middleware\FrameGuard;
use App\Http\Middleware\GrantFirstPartyToken;
use App\Http\Middleware\Localization;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\RestrictedAccess;
use App\Http\Middleware\TwoFactorAuth;
use App\Http\Middleware\VinylHubServiceAuth;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Passport\Http\Middleware\CheckToken;
use Laravel\Passport\Http\Middleware\CheckTokenForAnyScope;
use Laravel\Passport\Http\Middleware\CreateFreshApiToken;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        using: function () {
            Route::middleware('web')
                ->group(base_path('routes/web-admin.php'));

            Route::middleware('web')
                ->group(base_path('routes/web-portfolio.php'));

            Route::middleware('web')
                ->group(base_path('routes/web-api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            Route::middleware('api')
                ->group(base_path('routes/api.php'));
        },
        channels: __DIR__.'/../routes/channels.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->use([
            HandleCors::class,
            PreventRequestsDuringMaintenance::class,
            ValidatePostSize::class,
            TrustProxies::class,
            TrimStrings::class,
            ConvertEmptyStringsToNull::class,
        ]);

        $middleware->group('web', [
            EncryptCookies::class,
            FrameGuard::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            ValidateCsrfToken::class,
            SubstituteBindings::class,
            CreateFreshApiToken::class,
            'restricted',
        ]);

        $middleware->group('oauth-web', [
            EncryptCookies::class,
            FrameGuard::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            ValidateCsrfToken::class,
            SubstituteBindings::class,
            CreateFreshApiToken::class,
        ]);

        $middleware->group('api', [
            EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            'bindings',
            GrantFirstPartyToken::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'oauth/token',
        ]);

        $middleware->alias([
            'api.admin' => ApiAdmin::class,
            'admin' => Admin::class,
            'auth' => Authenticate::class,
            'auth.basic' => AuthenticateWithBasicAuth::class,
            'bindings' => SubstituteBindings::class,
            'cache.headers' => SetCacheHeaders::class,
            'can' => Authorize::class,
            'dangerzone' => RequirePassword::class,
            'localization' => Localization::class,
            'guest' => RedirectIfAuthenticated::class,
            'signed' => ValidateSignature::class,
            'throttle' => ThrottleRequests::class,
            'twofactor' => TwoFactorAuth::class,
            'validemail' => EmailVerificationCheck::class,
            'vinylhub.service' => VinylHubServiceAuth::class,
            'interstitial' => AccountInterstitial::class,
            'scopes' => CheckToken::class,
            'scope' => CheckTokenForAnyScope::class,
            'restricted' => RestrictedAccess::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('media:optimize')->hourlyAt(40)->onOneServer();
        $schedule->command('media:gc')->hourlyAt(5)->onOneServer();
        $schedule->command('horizon:snapshot')->everyFiveMinutes()->onOneServer();
        $schedule->command('story:gc')->everyFiveMinutes()->onOneServer();
        $schedule->command('gc:failedjobs')->dailyAt(3)->onOneServer();
        $schedule->command('gc:passwordreset')->dailyAt('09:41')->onOneServer();
        $schedule->command('gc:sessions')->twiceDaily(13, 23)->onOneServer();
        $schedule->command('storage:maintenance')->dailyAt('04:15')->onOneServer();
        $schedule->command('app:weekly-instance-scan')->weeklyOn(2, '4:20')->onOneServer();
        $schedule->command('app:cleanup-expired-app-registrations')->dailyAt(1)->onOneServer();
        $schedule->command('passport:purge')->everyFourHours(20)->onOneServer();

        if ((bool) config_cache('pixelfed.cloud_storage') && (bool) config_cache('media.delete_local_after_cloud')) {
            // Upload any local stragglers to cloud and GC verified local copies.
            $schedule->command('admin:MediaMoveStorageLocalToCloud --force --limit=500')->hourlyAt(15);
        }

        if (config('import.instagram.enabled')) {
            $schedule->command('app:transform-imports')->twiceDaily(13, 22)->onOneServer();
            $schedule->command('app:import-upload-garbage-collection')->hourlyAt(51)->onOneServer();
            $schedule->command('app:import-remove-deleted-accounts')->hourlyAt(37)->onOneServer();
            $schedule->command('app:import-upload-clean-storage')->twiceDailyAt(1, 13, 32)->onOneServer();

            if (config('import.instagram.storage.cloud.enabled') && (bool) config_cache('pixelfed.cloud_storage')) {
                $schedule->command('app:import-upload-media-to-cloud-storage')->hourlyAt(39)->onOneServer();
            }
        }

        $schedule->command('app:notification-epoch-update')->weeklyOn(1, '2:21')->onOneServer();
        $schedule->command('app:hashtag-cached-count-update')->hourlyAt(25)->onOneServer();
        $schedule->command('app:account-post-count-stat-update')->everySixHours(25)->onOneServer();
        $schedule->command('app:instance-update-total-local-posts')->twiceDailyAt(1, 13, 45)->onOneServer();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontReport([
            OAuthServerException::class,
            ConnectionException::class,
        ]);

        $exceptions->dontFlash([
            'password',
            'password_confirmation',
        ]);

        $exceptions->reportable(function (BadMethodCallException $e) {
            return app()->environment() !== 'production';
        });

        $exceptions->reportable(function (ConnectionException $e) {
            return app()->environment() !== 'production';
        });

        $exceptions->render(function (Throwable $e, $request) {
            if ($request->wantsJson()) {
                if ($e instanceof HttpResponseException) {
                    return $e->getResponse();
                }

                if ($e instanceof AuthenticationException) {
                    return response()->json(
                        ['error' => $e->getMessage()],
                        401,
                    );
                }

                if ($e instanceof ValidationException) {
                    return response()->json([
                        'message' => $e->getMessage(),
                        'errors' => $e->validator->getMessageBag(),
                    ], $e->status);
                }

                $isHttp = $e instanceof HttpExceptionInterface;

                return response()->json(
                    ['error' => $e->getMessage()],
                    $isHttp ? $e->getStatusCode() : 500,
                    $isHttp ? $e->getHeaders() : [],
                );
            }
        });
    })
    ->create();
