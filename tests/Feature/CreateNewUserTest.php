<?php

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| CreateNewUser — specific-case unit tests (task 5.5)
|--------------------------------------------------------------------------
|
| Exercises App\Actions\Fortify\CreateNewUser::create() directly (resolved from
| the container) rather than through the HTTP/Fortify pipeline, so each case
| isolates the action's own validation and persistence. The wider guardrail
| coverage lives in tests/Feature/CreateNewUserPropertyTest.php (P6); this file
| pins down the individual failure modes and the exact happy-path persistence.
|
| Covers Requirements 6.1 (agecheck), 6.2 (rt), 6.4 (banned email + uniqueness),
| 6.8 (bcrypt hash + app_register_ip), 6.9 (Purify-cleaned name).
|
| The suite defaults CACHE_STORE to redis and instance.enable_cc to true. Both
| are pinned to test-friendly values in beforeEach so config_cache() reads the
| plain config() value and the register-token cache lives in the array store.
| This mirrors tests/Feature/CreateNewUserPropertyTest.php.
|
*/

beforeEach(function () {
    // Pin the cache to the in-memory array store (the suite default is redis,
    // which is not guaranteed to be running) and start from a clean slate.
    config(['cache.default' => 'array']);
    Cache::store('array')->flush();

    // With instance.enable_cc disabled, config_cache($key) short-circuits to
    // config($key), making the registration toggles deterministic and DB-free.
    config(['instance.enable_cc' => false]);

    // Base valid environment: open registration, no max-user cap, no banned
    // signup IP, captcha OFF so h-captcha-response is never required. Each case
    // either supplies fully valid input or violates exactly one rule.
    config([
        'pixelfed.open_registration' => true,
        'pixelfed.enforce_max_users' => false,
        'pixelfed.max_users' => 1000,
        'pixelfed.min_password_length' => 8,
        'pixelfed.max_name_length' => 30,
        'pixelfed.bouncer.cloud_ips.ban_signups' => false,
        'captcha.enabled' => false,
        'captcha.active.register' => false,
    ]);

    // Seed a known, valid register token in the array cache. The action reads
    // the same 'pf:register:rt' key via CreateNewUser::getRegisterToken().
    Cache::store('array')->forever('pf:register:rt', 'valid-register-token');
});

/**
 * Build a fully valid registration input array.
 *
 * gmail.com resolves, so the email:rfc,dns,spoof rule does not flake on the
 * happy path. $seed keeps username/email/password unique across calls.
 *
 * @return array<string, mixed>
 */
function validInput(int $seed = 0): array
{
    $password = 'sup3r-secret-'.$seed;

    return [
        'name' => 'Valid User '.$seed,
        'username' => 'validuser'.$seed,
        'email' => 'pixelfed.t55.'.$seed.'@gmail.com',
        'password' => $password,
        'password_confirmation' => $password,
        'agecheck' => 'on',
        'rt' => CreateNewUser::getRegisterToken(),
    ];
}

/**
 * Resolve the action under test from the container.
 */
function createUserAction(): CreateNewUser
{
    return app(CreateNewUser::class);
}

it('throws and persists no user when agecheck is missing', function () {
    // Requirement 6.1 — agecheck is required|accepted.
    $input = validInput(1);
    unset($input['agecheck']);

    $before = User::count();

    expect(fn () => createUserAction()->create($input))
        ->toThrow(ValidationException::class);

    expect(User::count())->toBe($before);
});

it('throws and persists no user when agecheck is false', function () {
    // Requirement 6.1 — a non-accepted agecheck value is rejected.
    $input = validInput(2);
    $input['agecheck'] = '0';

    $before = User::count();

    expect(fn () => createUserAction()->create($input))
        ->toThrow(ValidationException::class);

    expect(User::count())->toBe($before);
});

it('throws and persists no user when the register token does not match', function () {
    // Requirement 6.2 — rt must equal the cached Register_Token.
    $input = validInput(3);
    $input['rt'] = 'not-the-valid-token';

    $before = User::count();

    expect(fn () => createUserAction()->create($input))
        ->toThrow(ValidationException::class);

    expect(User::count())->toBe($before);
});

it('throws and persists no user for a banned email domain', function () {
    // Requirement 6.4 — EmailService-banned addresses are rejected.
    $bannedEmail = 'someone@bugmenot.com';
    expect(EmailService::isBanned($bannedEmail))->toBeTrue();

    $input = validInput(4);
    $input['email'] = $bannedEmail;

    $before = User::count();

    expect(fn () => createUserAction()->create($input))
        ->toThrow(ValidationException::class);

    expect(User::count())->toBe($before);
});

it('throws and persists no user when the username is already taken', function () {
    // Requirement 6.4 — username must be unique across existing users.
    $existing = User::factory()->create();

    $input = validInput(5);
    $input['username'] = $existing->username;

    $before = User::count();

    expect(fn () => createUserAction()->create($input))
        ->toThrow(ValidationException::class);

    // No new user beyond the fixture was created.
    expect(User::count())->toBe($before);
});

it('persists one user with a bcrypt hash, request IP, and Purify-cleaned name for valid input', function () {
    // Requirements 6.8 (bcrypt password + app_register_ip) and 6.9 (Purify name).
    $input = validInput(6);
    // A name carrying markup Purify strips, kept within max_name_length so the
    // name rule passes and Purify is what removes the tag. Purify::clean()
    // strips the <script> element and its contents, yielding "Valid User".
    $input['name'] = 'Val<script>x</script>id User';

    $before = User::count();

    $user = createUserAction()->create($input);

    // Exactly one new user persisted.
    expect(User::count())->toBe($before + 1);
    expect($user->exists)->toBeTrue();
    expect($user->username)->toBe($input['username']);
    expect($user->email)->toBe($input['email']);

    // Password stored as a bcrypt hash that verifies against the plaintext,
    // and is never the plaintext itself.
    expect($user->password)->not->toBe($input['password']);
    expect(Hash::check($input['password'], $user->password))->toBeTrue();

    // app_register_ip comes from request()->ip(), which defaults to 127.0.0.1
    // when the action is invoked directly outside a real HTTP request.
    expect($user->app_register_ip)->toBe('127.0.0.1');

    // Purify::clean() strips the <script> markup (and its contents) from the
    // stored name, so the raw input is never persisted verbatim.
    expect($user->name)->not->toContain('<script>');
    expect($user->name)->toBe('Valid User');
});
