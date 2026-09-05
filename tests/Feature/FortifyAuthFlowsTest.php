<?php

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Fortify end-to-end auth flows (task 9.2)
|--------------------------------------------------------------------------
|
| Drives the full login, registration, and password-reset flows over HTTP
| through the Fortify pipeline and asserts the transparent-cutover contract:
| a successful login/register/reset authenticates and redirects to /i/web,
| while invalid input is rejected with errors, retains non-sensitive input,
| and never leaks the submitted password back into the session.
|
| Requests hit the URL PATHS directly (/login, /register, /reset-password)
| rather than route() names, because during the additive migration phase the
| legacy password.request/password.update route names still resolve to the
| old controllers; only the Fortify paths exercise the new pipeline.
|
| Covers Requirements 2.2, 2.3, 5.1, 5.2, 5.3, 5.5.
|
*/

beforeEach(function () {
    // The suite defaults CACHE_STORE to redis, which is not guaranteed to be
    // running in the test environment. Pin the cache to the in-memory array
    // store, then start from a clean slate. Mirrors
    // tests/Feature/FortifyAuthenticateClosureTest.php.
    config(['cache.default' => 'array']);
    Cache::store('array')->flush();

    // With instance.enable_cc disabled, config_cache($key) short-circuits to
    // config($key), making the auth toggles deterministic and DB-free.
    config(['instance.enable_cc' => false]);

    // Skip the login throttle middleware: it resolves a redis-backed limiter
    // store, and these tests exercise the flows, not the rate limiter (covered
    // separately for task 2.2).
    $this->withoutMiddleware(ThrottleRequests::class);

    // Fortify's PrepareAuthenticatedSession clears the login limiter on a
    // successful login via the RateLimiter singleton, which was resolved with
    // the redis store at boot. Rebind it to the array store so the success path
    // does not reach for redis.
    $this->app->instance(
        RateLimiter::class,
        new RateLimiter(Cache::store('array'))
    );

    // Keep captcha off and the bouncer bans disabled so no guardrail interferes
    // with the happy paths; open registration with no max-user cap so the
    // registration availability guard passes.
    config([
        'captcha.enabled' => false,
        'captcha.active.login' => false,
        'captcha.active.register' => false,
        'captcha.triggers.login.enabled' => false,
        'pixelfed.bouncer.cloud_ips.ban_logins' => false,
        'pixelfed.bouncer.cloud_ips.ban_signups' => false,
        'pixelfed.open_registration' => true,
        'pixelfed.enforce_max_users' => false,
        'pixelfed.max_users' => 1000,
        'pixelfed.min_password_length' => 8,
        'pixelfed.max_name_length' => 30,
    ]);

    // Seed a known register token in the array cache so the rt field is known
    // for the registration flow. CreateNewUser::getRegisterToken() reads the
    // same 'pf:register:rt' key.
    Cache::store('array')->forever('pf:register:rt', 'valid-register-token');
});

/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

it('logs a user in and redirects to /i/web with the correct password', function () {
    // Requirements 2.2, 5.1 — valid credentials complete authentication and
    // the HTML response redirects to /i/web.
    $user = User::factory()->create([
        'email' => 'login.success@gmail.com',
        'password' => Hash::make('correct-password'),
        'status' => null,
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ])->assertRedirect('/i/web');

    $this->assertAuthenticatedAs($user);
});

it('rejects a wrong password, stays unauthenticated, retains the email but not the password', function () {
    // Requirements 2.3, 5.5 — a failed login leaves the request unauthenticated,
    // surfaces an error, retains the non-sensitive email input, and never
    // repopulates the submitted password.
    $user = User::factory()->create([
        'email' => 'login.failure@gmail.com',
        'password' => Hash::make('correct-password'),
        'status' => null,
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors('email');

    $this->assertGuest();

    // Non-sensitive input is retained as old input; the password is not.
    // Fortify lowercases the login identifier (fortify.lowercase_usernames),
    // so the retained value is the lowercased email.
    $response->assertSessionHasInput('email', mb_strtolower($user->email));
    expect(session()->getOldInput('password'))->toBeNull();
});

/*
|--------------------------------------------------------------------------
| REGISTER
|--------------------------------------------------------------------------
*/

it('registers a user with valid input and redirects to /i/web authenticated', function () {
    // Requirements 2.2, 5.2 — a fully valid registration creates the user,
    // authenticates them, and redirects to /i/web.
    $before = User::count();

    $response = $this->post('/register', [
        'name' => 'Valid User',
        'username' => 'validuser',
        'email' => 'pixelfed.t92.register@gmail.com',
        'password' => 'sup3r-secret-pass',
        'password_confirmation' => 'sup3r-secret-pass',
        'agecheck' => 'on',
        'rt' => CreateNewUser::getRegisterToken(),
    ]);

    $response->assertRedirect('/i/web');
    $response->assertSessionHasNoErrors();

    expect(User::count())->toBe($before + 1);

    $created = User::where('username', 'validuser')->first();
    expect($created)->not->toBeNull();
    $this->assertAuthenticatedAs($created);
});

it('rejects invalid registration, creates no user, retains non-sensitive input but not the password', function () {
    // Requirements 2.3, 5.5 — missing agecheck fails validation: no user is
    // persisted, the response redirects back with errors, the non-sensitive
    // username/email are retained, and the password is not repopulated.
    $before = User::count();

    $response = $this->from('/register')->post('/register', [
        'name' => 'Invalid User',
        'username' => 'invaliduser',
        'email' => 'pixelfed.t92.invalid@gmail.com',
        'password' => 'sup3r-secret-pass',
        'password_confirmation' => 'sup3r-secret-pass',
        // agecheck intentionally omitted -> required|accepted fails
        'rt' => CreateNewUser::getRegisterToken(),
    ]);

    $response->assertRedirect('/register');
    $response->assertSessionHasErrors('agecheck');

    expect(User::count())->toBe($before);
    expect(User::where('username', 'invaliduser')->exists())->toBeFalse();

    // Non-sensitive fields are retained as old input; the password is not.
    $response->assertSessionHasInput('username', 'invaliduser');
    $response->assertSessionHasInput('email', 'pixelfed.t92.invalid@gmail.com');
    expect(session()->getOldInput('password'))->toBeNull();
});

/*
|--------------------------------------------------------------------------
| RESET
|--------------------------------------------------------------------------
*/

it('resets a password with a valid broker token and redirects to /i/web', function () {
    // Requirement 5.3 — a valid reset token updates the stored password hash
    // and the HTML response redirects to /i/web. Kept to a single reset case.
    // Fortify lowercases the request email (fortify.lowercase_usernames) before
    // the broker lookup, so the stored email must already be lowercase for the
    // minted token to resolve back to this user.
    $user = User::factory()->create([
        'email' => 'reset.flow@gmail.com',
        'password' => Hash::make('old-password'),
        'status' => null,
    ]);

    // Mint a real reset token via the same broker Fortify's reset controller
    // validates against (config/auth.php 'users' broker).
    $token = Password::broker()->createToken($user);

    $newPassword = 'brand-new-secret';

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ]);

    $response->assertRedirect('/i/web');
    $response->assertSessionHasNoErrors();

    $user->refresh();
    expect(Hash::check($newPassword, $user->password))->toBeTrue();
});
