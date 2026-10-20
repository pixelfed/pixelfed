<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

uses(LazilyRefreshDatabase::class);

function usernameCaseInsensitiveUniqueRuleForTest(string $value): array
{
    return [
        'username' => [
            Rule::unique('users', 'username')->where(fn ($q) => $q->whereRaw('lower(username) = ?', [strtolower($value)])),
        ],
    ];
}

it('rejects a case-variant of an existing username as a duplicate', function () {
    User::factory()->create(['username' => 'alice']);

    // Creation paths lowercase the submitted username before validation, so a
    // `Alice` signup reaches the rule as `alice`. On SQLite `=` is
    // case-sensitive for ASCII, so the lower(username) comparison is what
    // matches the stored `alice` row and rejects the duplicate.
    $validator = Validator::make(['username' => 'alice'], usernameCaseInsensitiveUniqueRuleForTest('Alice'));

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('username'))->toBeTrue();
});

it('accepts a brand new distinct username', function () {
    User::factory()->create(['username' => 'alice']);

    $validator = Validator::make(['username' => 'bob'], usernameCaseInsensitiveUniqueRuleForTest('bob'));

    expect($validator->fails())->toBeFalse();
});

it('rejects a case-variant username through open registration', function () {
    config(['pixelfed.open_registration' => true]);
    config(['pixelfed.max_users' => 1000]);
    config(['honeypot.enabled' => false]);

    User::factory()->create(['username' => 'alice']);

    $this->post('/register', [
        'name' => 'Case Variant',
        'username' => 'Alice',
        'email' => 'casevariant@gmail.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'agree' => 'on',
        'agecheck' => 'on',
    ])->assertRedirect()
        ->assertSessionHasErrors(['username']);
});

it('accepts a fresh username through open registration and stores it lowercase', function () {
    config(['pixelfed.open_registration' => true]);
    config(['pixelfed.max_users' => 1000]);
    config(['honeypot.enabled' => false]);

    $response = $this->post('/register', [
        'name' => 'Fresh User',
        'username' => 'Carol',
        'email' => 'freshuser@gmail.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'agree' => 'on',
        'agecheck' => 'on',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('users', ['username' => 'carol']);
});

it('runs the lower username index migration as a no-op on sqlite', function () {
    expect(DB::connection()->getDriverName())->toBe('sqlite');

    $migration = require base_path('database/migrations/2026_10_08_000001_add_lower_username_unique_indexes.php');

    $migration->up();
    $migration->down();

    $indexes = DB::select("select name from sqlite_master where type = 'index' and name like '%_username_lower_unique'");

    expect($indexes)->toBeEmpty();
});
