<?php

use App\Http\Middleware\AccountInterstitial;
use App\Http\Middleware\Admin;
use App\Http\Middleware\Api\Admin as ApiAdmin;
use App\Http\Middleware\FrameGuard;
use App\Http\Middleware\GrantFirstPartyToken;
use App\Http\Middleware\Localization;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\RestrictedAccess;
use App\Services\PendingLoginService;
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
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Passport\Http\Middleware\CheckToken;
use Laravel\Passport\Http\Middleware\CheckTokenForAnyScope;
use Laravel\Passport\Http\Middleware\CreateFreshApiToken;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
            PreventRequestForgery::class,
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
            PreventRequestForgery::class,
            SubstituteBindings::class,
            CreateFreshApiToken::class,
        ]);

        $middleware->group('api', [
            EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            'bindings',
            GrantFirstPartyToken::class,
        ]);

        $middleware->preventRequestForgery(except: [
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
            'interstitial' => AccountInterstitial::class,
            'scopes' => CheckToken::class,
            'scope' => CheckTokenForAnyScope::class,
            'restricted' => RestrictedAccess::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        require __DIR__.'/scheduledtasks.php';
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
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

        // A login-flow form submitted from a tab whose CSRF token is stale
        // (the session was regenerated by a login or a pending step elsewhere)
        // lands on wherever the session actually is instead of a 419.
        //
        // The handler rewrites TokenMismatchException into an HttpException(419)
        // (keeping the original as the previous exception) in prepareException()
        // before render callbacks run, so this must key on the rewritten type
        // and identify the CSRF case via status code + previous exception.
        // Returning null for anything else lets the JSON Throwable callback below
        // continue to handle XHR/JSON login requests as a 419.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419 || ! $e->getPrevious() instanceof TokenMismatchException) {
                return null;
            }

            if ($request->wantsJson() || ! $request->is('login', 'login/*')) {
                return null;
            }

            if (Auth::check()) {
                return redirect('/i/web');
            }

            if ($step = PendingLoginService::step($request)) {
                return redirect()->route('login', ['step' => $step]);
            }

            return redirect()->route('login')->withErrors([
                'login' => __('Your sign-in session expired. Please sign in again.'),
            ]);
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
