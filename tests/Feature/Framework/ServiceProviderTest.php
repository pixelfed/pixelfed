<?php

use App\Providers\AppServiceProvider;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Service Provider Boot Tests
|--------------------------------------------------------------------------
|
| Verify that the application boots without errors and all critical
| service providers register their bindings and routes correctly.
|
*/

it('boots the application without errors', function () {
    // If we reach this point, the app booted successfully
    expect(app()->isBooted())->toBeTrue();
});

it('registers the AppServiceProvider', function () {
    expect(app()->getProvider(AppServiceProvider::class))->not->toBeNull();
});

it('resolves the Passport token guard', function () {
    $guard = auth()->guard('api');

    // Passport registers a token guard via RequestGuard or TokenGuard
    expect($guard)->not->toBeNull();
});

it('resolves the web session guard', function () {
    $guard = auth()->guard('web');

    expect($guard)->toBeInstanceOf(SessionGuard::class);
});

it('has routes loaded', function () {
    $routes = Route::getRoutes();

    expect($routes->count())->toBeGreaterThan(100);
});

it('binds the config_cache helper', function () {
    // app.name is ENVCONFIG and env-authoritative under the test env (APP_NAME
    // present), so config_cache() returns config(). This confirms the helper is
    // bound and resolves a sensible value with the config cache always on (the
    // master switch has been removed).
    expect(config_cache('app.name'))->toBe(config('app.name'));

    // An unlisted key passes straight through to config().
    expect(config_cache('app.url'))->toBe(config('app.url'));
});
