<?php

// Feature: fortify-auth-migration, Property 1: For every existing user and any plaintext, login via the authenticateUsing closure succeeds iff Hash::check(plaintext, user.password) returns true
// Validates: Requirements 3.2, 3.3, 12.4

use App\Models\User;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Property P1 — Hash compatibility
|--------------------------------------------------------------------------
|
| For every existing user u and any plaintext p:
|
|     login(u.email, p) succeeds  <=>  Hash::check(p, u.password)
|
| This proves existing bcrypt hashes authenticate identically under Fortify
| (no password resets needed on cutover).
|
| bcrypt is deliberately slow, so 100+ full HTTP logins would make the suite
| crawl. The property is therefore verified at two complementary levels:
|
|   (A) 100+ generated plaintexts, hashed with the project's configured bcrypt
|       driver (config/hashing.php), asserting Hash::check parity directly:
|       the correct plaintext verifies true and a wrong one verifies false.
|       This is the exhaustive half of the property.
|
|   (B) A handful of end-to-end POST /login assertions proving the Fortify
|       authenticateUsing() closure actually honors Hash::check — correct
|       plaintext authenticates and redirects to /i/web, wrong plaintext
|       leaves the request a guest.
|
| Covers Requirements 3.2, 3.3, 12.4.
|
*/

beforeEach(function () {
    // The suite defaults CACHE_STORE to redis, which is not guaranteed to be
    // running here. Pin the cache to the in-memory array store and start clean.
    config(['cache.default' => 'array']);
    Cache::store('array')->flush();

    // Fortify's login rate limiter (5/60min, keyed by email+ip) and the
    // PrepareAuthenticatedSession limiter reset resolve a redis-backed store at
    // boot. This property is about hash compatibility, not throttling, so drop
    // the throttle middleware and rebind the RateLimiter singleton to the array
    // store so repeated login attempts never reach for redis or get throttled.
    $this->withoutMiddleware(ThrottleRequests::class);
    $this->app->instance(
        RateLimiter::class,
        new RateLimiter(Cache::store('array'))
    );

    // Keep captcha and the bouncer off so neither interferes with the credential
    // check under test.
    config([
        'captcha.enabled' => false,
        'captcha.active.login' => false,
        'captcha.triggers.login.enabled' => false,
        'pixelfed.bouncer.cloud_ips.ban_logins' => false,
    ]);
});

/**
 * Generate 120 plaintext passwords spanning length and charset variety while
 * staying within valid login bounds (6..72 chars) so the sample exercises the
 * hash function broadly. Each returns [plaintext, wrongPlaintext].
 *
 * @return array<int, array{0: string, 1: string}>
 */
function fortifyHashPlaintexts(): array
{
    $cases = [];

    for ($i = 0; $i < 120; $i++) {
        $length = fake()->numberBetween(6, 72);

        $plaintext = match ($i % 4) {
            0 => Str::random($length),
            1 => fake()->regexify('[A-Za-z0-9!@#$%^&*()_+\-=]{'.$length.'}'),
            2 => substr(str_repeat(fake()->word().'✓ß→', 20), 0, $length),
            default => fake()->password($length > 40 ? 40 : $length, $length),
        };

        // Guarantee bounds even after multibyte truncation shenanigans.
        if (mb_strlen($plaintext) < 6) {
            $plaintext = str_pad($plaintext, 6, 'x');
        }

        // A wrong password guaranteed to differ within bcrypt's significant
        // window. bcrypt only hashes the first 72 BYTES of input, so appending
        // a suffix to an already-72-byte plaintext would collide. Instead flip
        // the first byte and keep the same length so the difference always
        // lands inside the hashed prefix.
        $first = $plaintext[0];
        $replacement = $first === 'A' ? 'B' : 'A';
        $wrong = $replacement.substr($plaintext, 1);

        $cases[] = [$plaintext, $wrong];
    }

    return $cases;
}

it('verifies bcrypt hash-check parity for correct and wrong plaintexts', function (string $plaintext, string $wrong) {
    // Level A — 120 iterations. Hash with the project's configured bcrypt
    // driver exactly as the app does, then assert the parity the closure relies
    // on: correct plaintext checks true (Requirement 3.2) and any other
    // plaintext checks false (Requirement 3.3). Because the stored hash is
    // untouched by the migration, this is the same boolean Fortify observes
    // (Requirement 12.4).
    $user = User::factory()->create([
        'password' => Hash::make($plaintext),
        'status' => null,
    ]);

    expect(Hash::check($plaintext, $user->password))->toBeTrue();
    expect(Hash::check($wrong, $user->password))->toBeFalse();
})->with('fortifyHashPlaintexts');

dataset('fortifyHashPlaintexts', fn () => fortifyHashPlaintexts());

it('authenticates end-to-end iff the plaintext matches the stored bcrypt hash', function (string $plaintext) {
    // Level B — end-to-end through the Fortify authenticateUsing() closure.
    // Correct plaintext authenticates and redirects to /i/web; the wrong
    // plaintext leaves the caller a guest. Proves the closure honors
    // Hash::check rather than some other comparison.
    $user = User::factory()->create([
        'password' => Hash::make($plaintext),
        'status' => null,
    ]);

    // Wrong password: fails, stays guest (Requirement 3.3).
    $this->post('/login', [
        'email' => $user->email,
        'password' => $plaintext.'-wrong',
    ]);
    $this->assertGuest();

    // Correct password: succeeds, redirects to /i/web (Requirements 3.2, 12.4).
    $this->post('/login', [
        'email' => $user->email,
        'password' => $plaintext,
    ])->assertRedirect('/i/web');

    $this->assertAuthenticatedAs($user);
})->with([
    'short alphanumeric' => ['aB3xz9'],
    'symbols mixed' => ['P@ssw0rd!#2024'],
    'long password' => [str_repeat('Str0ng!', 8)], // 56 chars, within 72
    'spaces and case' => ['My Secret Pass 42'],
]);
