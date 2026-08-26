<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->group(base_path('routes/web-admin.php'));

        Route::middleware('web')
            ->group(base_path('routes/web-portfolio.php'));

        Route::middleware('web')
            ->group(base_path('routes/web-api.php'));

        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::middleware('api')
            ->group(base_path('routes/api.php'));
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('app-signup', function (Request $request) {
            return Limit::perDay(100)->by($request->ip());
        });

        RateLimiter::for('app-code-verify', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email')));

            $emailKey = $email !== ''
                ? hash('sha256', $email)
                : 'missing';

            return [
                Limit::perHour(20)->by('app-code-verify:ip:'.$request->ip()),
                Limit::perHour(10)->by('app-code-verify:email:'.$emailKey),
            ];
        });

        RateLimiter::for('app-code-resend', function (Request $request) {
            return Limit::perHour(10)->by($request->ip());
        });

        RateLimiter::for('account-lookup', function (Request $request) {
            return Limit::perDay(50)->by($request->ip());
        });

        RateLimiter::for('oauth-pat', function (Request $request) {
            $user = $request->user('web');

            $actor = $user
                ? 'u:'.$user->getAuthIdentifier()
                : 'ip:'.$request->ip();

            return [
                Limit::perMinute(3)
                    ->by("minute:{$actor}"),

                Limit::perHour(15)
                    ->by("hour:{$actor}"),

                Limit::perDay(20)
                    ->by("day:{$actor}"),
            ];
        });
    }
}
