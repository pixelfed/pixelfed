<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Password Reset
|--------------------------------------------------------------------------
|
| Exercises the Fortify password-reset flow over HTTP. After the cutover
| (task 10.1) the legacy Auth::routes() paths are gone and Fortify serves the
| reset flow at NEW paths: GET/POST /forgot-password and GET/POST
| /reset-password. The named routes are unchanged, so requests use route()
| names to stay path-agnostic:
|   - password.request  GET  /forgot-password  (auth.passwords.email view)
|   - password.email    POST /forgot-password  (send reset link)
|   - password.reset    GET  /reset-password/{token} (auth.passwords.reset view)
|   - password.update   POST /reset-password   (perform the reset)
|
| Fortify lowercases the login identifier (fortify.lowercase_usernames) before
| the broker lookup, so users are created with a lowercase email and the minted
| token resolves back to them.
|
*/

beforeEach(function () {
    // The suite defaults CACHE_STORE to redis, which is not guaranteed to be
    // running in the test environment. Pin the cache to the in-memory array
    // store, then start from a clean slate. Mirrors
    // tests/Feature/FortifyAuthenticateClosureTest.php.
    config(['cache.default' => 'array']);
    Cache::store('array')->flush();

    // These tests exercise the reset flow, not the rate limiter. The throttle
    // middleware resolves a redis-backed limiter store, so skip it here.
    $this->withoutMiddleware(ThrottleRequests::class);

    // Fortify's PrepareAuthenticatedSession clears the login limiter via the
    // RateLimiter singleton, which was resolved with the redis store at boot.
    // Rebind it to the array store so the success path does not reach for redis.
    $this->app->instance(
        RateLimiter::class,
        new RateLimiter(Cache::store('array'))
    );
});

it('renders the password reset request page', function () {
    $this->get(route('password.request'))
        ->assertOk();
});

it('sends a password reset link to a valid email', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'reset.request@gmail.com',
    ]);

    $this->post(route('password.email'), [
        'email' => $user->email,
    ])->assertRedirect();

    Notification::assertSentTo($user, ResetPassword::class);
});

it('does not reveal whether an email exists', function () {
    Notification::fake();

    $this->post(route('password.email'), [
        'email' => 'nonexistent@example.com',
    ])->assertRedirect();

    Notification::assertNothingSent();
});

it('renders the password reset form with a valid token', function () {
    $user = User::factory()->create([
        'email' => 'reset.form@gmail.com',
    ]);
    $token = Password::createToken($user);

    $this->get(route('password.reset', $token))
        ->assertOk();
});

it('resets the password with a valid token', function () {
    $user = User::factory()->create([
        'email' => 'reset.valid@gmail.com',
        'status' => null,
    ]);
    $token = Password::broker()->createToken($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewSecurePass123!',
        'password_confirmation' => 'NewSecurePass123!',
    ])->assertRedirect();

    $user->refresh();
    expect(Hash::check('NewSecurePass123!', $user->password))->toBeTrue();
});

it('rejects password reset with an invalid token', function () {
    $user = User::factory()->create([
        'email' => 'reset.invalid@gmail.com',
        'password' => Hash::make('original-password'),
    ]);

    $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'NewSecurePass123!',
        'password_confirmation' => 'NewSecurePass123!',
    ])->assertRedirect();

    $user->refresh();
    expect(Hash::check('original-password', $user->password))->toBeTrue();
});
