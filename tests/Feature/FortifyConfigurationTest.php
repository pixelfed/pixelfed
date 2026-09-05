<?php

use App\Providers\FortifyServiceProvider;
use Laravel\Fortify\Features;

/*
|--------------------------------------------------------------------------
| Fortify Configuration & Provider Registration Tests
|--------------------------------------------------------------------------
|
| Verifies that Fortify is wired into Pixelfed as the headless auth backend:
| the FortifyServiceProvider is loaded, the core config values resolve to the
| Pixelfed-specific settings, only registration + password-reset features are
| enabled, and the boot-time configuration guard rejects invalid config.
|
| Covers Requirements 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9.
|
*/

it('registers the FortifyServiceProvider in the loaded provider list', function () {
    // Requirement 1.2
    expect(app()->getLoadedProviders())
        ->toHaveKey(FortifyServiceProvider::class);
});

it('configures the web guard and users password broker', function () {
    // Requirements 1.3
    expect(config('fortify.guard'))->toBe('web');
    expect(config('fortify.passwords'))->toBe('users');
});

it('uses email as the Fortify login username field', function () {
    // Requirement 1.4
    expect(config('fortify.username'))->toBe('email');
});

it('redirects to the Pixelfed home path after auth', function () {
    // Redirect destination consumed by the response contracts.
    expect(config('fortify.home'))->toBe('/i/web');
});

it('enables the registration feature', function () {
    // Requirement 1.5
    expect(Features::enabled(Features::registration()))->toBeTrue();
});

it('enables the password-reset feature', function () {
    // Requirement 1.6
    expect(Features::enabled(Features::resetPasswords()))->toBeTrue();
});

it('leaves two-factor authentication disabled', function () {
    // Requirement 1.7 — Pixelfed keeps its own custom Google2FA flow.
    expect(Features::enabled(Features::twoFactorAuthentication()))->toBeFalse();
});

it('leaves email verification disabled', function () {
    // Requirement 1.8 — Pixelfed keeps its own custom EmailVerification flow.
    expect(Features::enabled(Features::emailVerification()))->toBeFalse();
});

it('enables only registration and password resets', function () {
    // Requirements 1.5, 1.6, 1.7, 1.8 — assert the exact feature set.
    expect(config('fortify.features'))
        ->toBe([
            Features::registration(),
            Features::resetPasswords(),
        ]);
});

it('halts boot when a required core config value is null', function () {
    // Requirement 1.9 — a null guard must surface a startup error rather than
    // silently registering Fortify auth routes against an invalid config.
    config(['fortify.guard' => null]);

    $provider = new FortifyServiceProvider(app());

    $guard = (new ReflectionClass($provider))
        ->getMethod('guardFortifyConfiguration');
    $guard->setAccessible(true);

    expect(fn () => $guard->invoke($provider))
        ->toThrow(RuntimeException::class);
});

it('boots normally when core config values are present', function () {
    // Requirement 1.9 — the guard passes with valid config (no exception).
    $provider = new FortifyServiceProvider(app());

    $guard = (new ReflectionClass($provider))
        ->getMethod('guardFortifyConfiguration');
    $guard->setAccessible(true);

    expect(fn () => $guard->invoke($provider))->not->toThrow(RuntimeException::class);
});
