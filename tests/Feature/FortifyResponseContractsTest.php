<?php

use App\Http\Responses\LoginResponse;
use App\Http\Responses\PasswordResetResponse;
use App\Http\Responses\RegisterResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Fortify response contracts — redirect parity, AccountLog, JSON parity
|--------------------------------------------------------------------------
|
| Covers task 3.5 for the fortify-auth-migration spec. Three concerns:
|
|   1. Property P3 (redirect parity): a successful login, registration, or
|      reset produces an HTML response that redirects to /i/web.
|   2. AccountLog: LoginResponse writes exactly one auth.login record for a
|      non-deleted user and none for a deleted-status user.
|   3. JSON parity: wantsJson() callers receive a JSON payload indicating the
|      /i/web redirect destination for login/register/reset.
|
| Approach for P3 (documented per task instructions)
| ---------------------------------------------------
| bcrypt is deliberately slow, so 100+ full HTTP logins would make the suite
| crawl. P3 is verified at two complementary levels:
|
|   (A) The exhaustive half — 120 generated request states per Response class.
|       Each Response contract's toResponse() is called directly with a faked
|       HTML Illuminate\Http\Request (built via Request::create with a resolved
|       user where relevant, and NO application/json Accept header so
|       wantsJson() is false). We assert every one returns a RedirectResponse
|       whose target URL path is /i/web. Varied state (IP, user agent, path,
|       query string, user status) proves the redirect target is invariant.
|
|   (B) A handful of true end-to-end POST /login assertions proving the wiring
|       through Fortify still lands the browser on /i/web.
|
| Registration and reset redirects are exercised at level (A) only: their
| Response classes have no side effects and no bcrypt dependency, so the direct
| toResponse() call is the faithful and exhaustive check. Login additionally
| gets the end-to-end pass because it carries the AccountLog side effect.
|
| Validates Requirements 4.3, 4.4, 5.1, 5.2, 5.3, 5.4.
|
*/

beforeEach(function () {
    // The suite defaults CACHE_STORE to redis, which is not guaranteed to be
    // running here. Pin the cache to the in-memory array store and start clean.
    config(['cache.default' => 'array']);
    Cache::store('array')->flush();

    // The end-to-end login case posts to /login. Fortify's login limiter and
    // PrepareAuthenticatedSession reset resolve a redis-backed store at boot;
    // drop the throttle middleware and rebind the RateLimiter singleton to the
    // array store so the login path never reaches for redis. Mirrors
    // tests/Feature/FortifyAuthenticateClosureTest.php.
    $this->withoutMiddleware(ThrottleRequests::class);
    $this->app->instance(
        RateLimiter::class,
        new RateLimiter(Cache::store('array'))
    );

    config([
        'captcha.enabled' => false,
        'captcha.active.login' => false,
        'captcha.triggers.login.enabled' => false,
        'pixelfed.bouncer.cloud_ips.ban_logins' => false,
    ]);
});

/**
 * Build a faked HTML request for a direct toResponse() call. No JSON Accept
 * header, so wantsJson() is false and the redirect branch is taken.
 *
 * @param  array<string, string>  $server
 */
function fortifyHtmlRequest(string $path = '/login', array $server = [], ?User $user = null): Request
{
    $request = Request::create($path, 'POST', [], [], [], $server);

    if ($user !== null) {
        $request->setUserResolver(fn () => $user);
    }

    return $request;
}

/**
 * 120 varied request states: differing IP, user agent, path, and query so the
 * redirect target is proven invariant across request shape.
 *
 * @return array<int, array{0: string, 1: array<string, string>}>
 */
function fortifyRedirectRequestStates(): array
{
    $cases = [];

    for ($i = 0; $i < 120; $i++) {
        $path = match ($i % 4) {
            0 => '/login',
            1 => '/register',
            2 => '/reset-password',
            default => '/some/deep/path?next='.Str::random(6),
        };

        $server = [
            'REMOTE_ADDR' => sprintf(
                '%d.%d.%d.%d',
                fake()->numberBetween(1, 254),
                fake()->numberBetween(0, 254),
                fake()->numberBetween(0, 254),
                fake()->numberBetween(1, 254),
            ),
            'HTTP_USER_AGENT' => fake()->userAgent(),
        ];

        $cases[] = [$path, $server];
    }

    return $cases;
}

dataset('fortifyRedirectRequestStates', fn () => fortifyRedirectRequestStates());

/**
 * Assert an HTTP response is a redirect landing on /i/web.
 */
function assertRedirectsToWeb(mixed $response): void
{
    expect($response)->toBeInstanceOf(RedirectResponse::class);
    expect(parse_url($response->getTargetUrl(), PHP_URL_PATH))->toBe('/i/web');
}

// -----------------------------------------------------------------------------
// Property P3 — redirect parity (Level A: exhaustive, 120 iterations each)
// -----------------------------------------------------------------------------

// Feature: fortify-auth-migration, Property 3: For any successful login, registration, or reset, the HTML response redirects to /i/web
// Validates: Requirements 4.3, 4.4, 5.1, 5.2, 5.3, 5.4
it('redirects successful HTML logins to /i/web across varied request state', function (string $path, array $server) {
    // Requirement 5.1 — a non-deleted user's successful login redirects to
    // /i/web regardless of request IP/UA/path.
    $user = User::factory()->create(['status' => null]);

    $response = (new LoginResponse)->toResponse(
        fortifyHtmlRequest($path, $server, $user)
    );

    assertRedirectsToWeb($response);
})->with('fortifyRedirectRequestStates');

it('redirects successful HTML registrations to /i/web across varied request state', function (string $path, array $server) {
    // Requirement 5.2 — registration redirect parity. No user side effects, so
    // a userless faked request is faithful.
    $response = (new RegisterResponse)->toResponse(
        fortifyHtmlRequest($path, $server)
    );

    assertRedirectsToWeb($response);
})->with('fortifyRedirectRequestStates');

it('redirects successful HTML password resets to /i/web across varied request state', function (string $path, array $server) {
    // Requirement 5.3 — password-reset redirect parity.
    $response = (new PasswordResetResponse)->toResponse(
        fortifyHtmlRequest($path, $server)
    );

    assertRedirectsToWeb($response);
})->with('fortifyRedirectRequestStates');

// -----------------------------------------------------------------------------
// Property P3 — redirect parity (Level B: a few true end-to-end logins)
// -----------------------------------------------------------------------------

it('lands the browser on /i/web after a real end-to-end login', function (string $plaintext) {
    // End-to-end proof that the LoginResponse is actually wired into Fortify
    // and honored on a real POST /login (Requirement 5.1).
    $user = User::factory()->create([
        'password' => Hash::make($plaintext),
        'status' => null,
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => $plaintext,
    ])->assertRedirect('/i/web');

    $this->assertAuthenticatedAs($user);
})->with([
    'short alphanumeric' => ['aB3xz9'],
    'symbols mixed' => ['P@ssw0rd!#2024'],
    'spaces and case' => ['My Secret Pass 42'],
]);

// -----------------------------------------------------------------------------
// AccountLog audit write (Requirements 4.3, 4.4)
// -----------------------------------------------------------------------------

it('writes exactly one auth.login AccountLog for a non-deleted user with ip and user agent', function () {
    // Requirement 4.3 — one record with the action, user id, IP, and UA.
    $user = User::factory()->create(['status' => null]);

    $request = fortifyHtmlRequest('/login', [
        'REMOTE_ADDR' => '203.0.113.7',
        'HTTP_USER_AGENT' => 'PixelfedTest/1.0',
    ], $user);

    (new LoginResponse)->toResponse($request);

    $this->assertDatabaseCount('account_logs', 1);
    $this->assertDatabaseHas('account_logs', [
        'user_id' => $user->id,
        'item_id' => $user->id,
        'item_type' => User::class,
        'action' => 'auth.login',
        'ip_address' => '203.0.113.7',
        'user_agent' => 'PixelfedTest/1.0',
    ]);
});

it('writes no AccountLog for a deleted-status user', function () {
    // Requirement 4.4 — a deleted account produces no audit record.
    $user = User::factory()->create(['status' => 'deleted']);

    (new LoginResponse)->toResponse(
        fortifyHtmlRequest('/login', ['REMOTE_ADDR' => '203.0.113.7'], $user)
    );

    $this->assertDatabaseCount('account_logs', 0);
});

// -----------------------------------------------------------------------------
// JSON parity (Requirement 5.4)
// -----------------------------------------------------------------------------

/**
 * Build a faked JSON request: Accept: application/json makes wantsJson() true.
 */
function fortifyJsonRequest(?User $user = null): Request
{
    $request = Request::create('/login', 'POST', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    if ($user !== null) {
        $request->setUserResolver(fn () => $user);
    }

    return $request;
}

it('returns a JSON payload indicating /i/web for a login wantsJson caller', function () {
    // Requirement 5.4 — JSON login response indicates /i/web, no HTTP redirect.
    $user = User::factory()->create(['status' => null]);

    $response = (new LoginResponse)->toResponse(fortifyJsonRequest($user));

    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->getData(true))->toMatchArray(['redirect' => '/i/web']);
});

it('returns a JSON payload indicating /i/web for a registration wantsJson caller', function () {
    // Requirement 5.4 — JSON registration response indicates /i/web.
    $response = (new RegisterResponse)->toResponse(fortifyJsonRequest());

    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->getData(true))->toMatchArray(['redirect' => '/i/web']);
});

it('returns a JSON payload indicating /i/web for a password-reset wantsJson caller', function () {
    // Requirement 5.4 — JSON password-reset response indicates /i/web.
    $response = (new PasswordResetResponse)->toResponse(fortifyJsonRequest());

    expect($response)->toBeInstanceOf(JsonResponse::class);
    expect($response->getData(true))->toMatchArray(['redirect' => '/i/web']);
});
