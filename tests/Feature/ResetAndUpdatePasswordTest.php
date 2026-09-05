<?php

use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| ResetUserPassword / UpdateUserPassword actions
|--------------------------------------------------------------------------
|
| Exercises the two Fortify password-write actions directly (resolved from the
| container) rather than through HTTP, so each validation branch and the hash
| persistence are the single subject under test.
|
| Covers Requirements 7.1, 7.3, 7.4, 8.2, 8.3, 8.4, 8.5.
|
*/

beforeEach(function () {
    // The suite defaults CACHE_STORE to redis, which is not guaranteed to be
    // available in the test environment. ResetUserPassword::reset() reads
    // config_cache('captcha.enabled'), which touches the cache, so pin it to
    // the in-memory array store and start clean.
    config(['cache.default' => 'array']);
    Cache::store('array')->flush();

    // Keep captcha off for the reset path so h-captcha-response is never
    // required by the cases below.
    config([
        'captcha.enabled' => false,
        'captcha.active.register' => false,
    ]);
});

// A password that satisfies Password::defaults() (>= 8 chars) and is <= 72.
$validPassword = 'new-valid-password';

it('rejects a reset password longer than 72 characters and leaves the stored password unchanged', function () {
    // Requirement 7.1 / 7.4 — max:72 is enforced; failure persists nothing.
    $user = User::factory()->create([
        'password' => Hash::make('known-current'),
    ]);
    $original = $user->password;

    $tooLong = str_repeat('a', 73);

    expect(fn () => app(ResetUserPassword::class)->reset($user, [
        'password' => $tooLong,
        'password_confirmation' => $tooLong,
    ]))->toThrow(ValidationException::class);

    expect($user->fresh()->password)->toBe($original);
    expect(Hash::check($tooLong, $user->fresh()->password))->toBeFalse();
});

it('rejects a reset when the password confirmation is missing and leaves the stored password unchanged', function () use ($validPassword) {
    // Requirement 7.1 / 7.4 — the "confirmed" rule fails without confirmation.
    $user = User::factory()->create([
        'password' => Hash::make('known-current'),
    ]);
    $original = $user->password;

    expect(fn () => app(ResetUserPassword::class)->reset($user, [
        'password' => $validPassword,
    ]))->toThrow(ValidationException::class);

    expect($user->fresh()->password)->toBe($original);
});

it('rejects a reset when the password confirmation does not match and leaves the stored password unchanged', function () use ($validPassword) {
    // Requirement 7.1 / 7.4 — mismatched confirmation fails validation.
    $user = User::factory()->create([
        'password' => Hash::make('known-current'),
    ]);
    $original = $user->password;

    expect(fn () => app(ResetUserPassword::class)->reset($user, [
        'password' => $validPassword,
        'password_confirmation' => 'different-password',
    ]))->toThrow(ValidationException::class);

    expect($user->fresh()->password)->toBe($original);
});

it('persists a new bcrypt hash for a valid confirmed reset password', function () use ($validPassword) {
    // Requirement 7.3 — a valid, confirmed password <= 72 chars is hashed and
    // stored, replacing the previous hash.
    $user = User::factory()->create([
        'password' => Hash::make('known-current'),
    ]);

    app(ResetUserPassword::class)->reset($user, [
        'password' => $validPassword,
        'password_confirmation' => $validPassword,
    ]);

    $stored = $user->fresh()->password;

    expect(Hash::check($validPassword, $stored))->toBeTrue();
    expect(Hash::check('known-current', $stored))->toBeFalse();
});

it('persists a new bcrypt hash when the current password is correct and the new password is valid', function () use ($validPassword) {
    // Requirement 8.3 — a valid current_password + valid new password updates
    // the stored hash. current_password:web validates against the web guard, so
    // the user must be authenticated.
    $user = User::factory()->create([
        'password' => Hash::make('known-current'),
    ]);
    $this->actingAs($user);

    app(UpdateUserPassword::class)->update($user, [
        'current_password' => 'known-current',
        'password' => $validPassword,
        'password_confirmation' => $validPassword,
    ]);

    $stored = $user->fresh()->password;

    expect(Hash::check($validPassword, $stored))->toBeTrue();
    expect(Hash::check('known-current', $stored))->toBeFalse();
});

it('rejects a password update when the current password is wrong and leaves the stored password unchanged', function () use ($validPassword) {
    // Requirement 8.5 — a mismatched current_password rejects the update.
    $user = User::factory()->create([
        'password' => Hash::make('known-current'),
    ]);
    $this->actingAs($user);
    $original = $user->password;

    expect(fn () => app(UpdateUserPassword::class)->update($user, [
        'current_password' => 'wrong-current',
        'password' => $validPassword,
        'password_confirmation' => $validPassword,
    ]))->toThrow(ValidationException::class);

    expect($user->fresh()->password)->toBe($original);
});

it('rejects a password update when the new password exceeds 72 characters and leaves the stored password unchanged', function () {
    // Requirement 8.2 / 8.4 — the new password max:72 rule is enforced.
    $user = User::factory()->create([
        'password' => Hash::make('known-current'),
    ]);
    $this->actingAs($user);
    $original = $user->password;

    $tooLong = str_repeat('a', 73);

    expect(fn () => app(UpdateUserPassword::class)->update($user, [
        'current_password' => 'known-current',
        'password' => $tooLong,
        'password_confirmation' => $tooLong,
    ]))->toThrow(ValidationException::class);

    expect($user->fresh()->password)->toBe($original);
});

it('rejects a password update when the new password is unconfirmed and leaves the stored password unchanged', function () use ($validPassword) {
    // Requirement 8.2 / 8.4 — the "confirmed" rule is enforced on the new
    // password.
    $user = User::factory()->create([
        'password' => Hash::make('known-current'),
    ]);
    $this->actingAs($user);
    $original = $user->password;

    expect(fn () => app(UpdateUserPassword::class)->update($user, [
        'current_password' => 'known-current',
        'password' => $validPassword,
        'password_confirmation' => 'different-password',
    ]))->toThrow(ValidationException::class);

    expect($user->fresh()->password)->toBe($original);
});
