<?php

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Register-token source — no legacy controller reference (Task 7.2)
|--------------------------------------------------------------------------
|
| Task 7.1 repointed the register token off the legacy RegisterController onto
| the non-controller source App\Actions\Fortify\CreateNewUser::getRegisterToken().
| This file verifies that the rendered auth.register view carries that token in
| its hidden `rt` input, and that neither the register view nor the parental-
| controls invite-register view references any deleted Auth controller class.
|
| The GET /register path is hit directly (not by route name) because during the
| additive migration phase both Auth::routes() and the Fortify routes register
| colliding route names — mirrors tests/Feature/FortifyAuthViewsTest.php.
|
| Covers Requirement 9.5.
|
*/

beforeEach(function () {
    // The suite defaults CACHE_STORE to redis, which is not guaranteed to be
    // running here. getRegisterToken() uses Cache::remember and config_cache()
    // is cache-backed, so pin the cache to the in-memory array store and start
    // clean. Mirrors tests/Feature/FortifyAuthViewsTest.php.
    config(['cache.default' => 'array']);
    Cache::store('array')->flush();

    // Guest GET route; the throttle middleware resolves a redis-backed limiter
    // store, so skip it to keep redis out of the picture.
    $this->withoutMiddleware(ThrottleRequests::class);

    // Keep the bouncer disabled so the page is reachable without a CIDR cache.
    config([
        'pixelfed.bouncer.cloud_ips.ban_logins' => false,
        'pixelfed.bouncer.cloud_ips.ban_signups' => false,
    ]);

    // Ensure registration is open so the register GET page is reachable.
    config(['pixelfed.open_registration' => true]);
});

it('renders the register view with the token from the non-controller source', function () {
    // Requirement 9.5 — the rendered view contains a registration token value
    // sourced from CreateNewUser::getRegisterToken(), not a controller method.
    $token = CreateNewUser::getRegisterToken();

    $this->get('/register')
        ->assertOk()
        ->assertViewIs('auth.register')
        ->assertSee('name="rt" value="'.$token.'"', false)
        ->assertSee($token, false);
});

it('register blade does not reference any deleted Auth controller class', function () {
    // Requirement 9.5 — the view produces no reference to a deleted controller.
    $blade = file_get_contents(resource_path('views/auth/register.blade.php'));

    expect(str_contains($blade, 'Auth\\RegisterController'))->toBeFalse();
    expect(str_contains($blade, 'Auth\RegisterController'))->toBeFalse();
});

it('parental-controls invite-register blade does not reference any deleted Auth controller class', function () {
    // Requirement 9.5 — the sibling registration view is likewise repointed.
    $blade = file_get_contents(resource_path('views/settings/parental-controls/invite-register-form.blade.php'));

    expect(str_contains($blade, 'Auth\\RegisterController'))->toBeFalse();
    expect(str_contains($blade, 'Auth\RegisterController'))->toBeFalse();
});

it('keeps the register token stable within the cache window', function () {
    // Requirement 9.5 — the token is a stable cached value, so the value the
    // view renders matches what registration validates against.
    expect(CreateNewUser::getRegisterToken())->toBe(CreateNewUser::getRegisterToken());
});
