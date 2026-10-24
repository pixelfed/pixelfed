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
    config(['instance.enable_cc' => false]);

    // config_cache falls back to config() when CC is disabled
    expect(config_cache('app.name'))->toBe(config('app.name'));
});
