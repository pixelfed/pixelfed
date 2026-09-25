<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Settings home storage-limit computation
|--------------------------------------------------------------------------
|
| /settings/home computed percentUsed by dividing by max_account_size * 1024
| unconditionally. When that config resolved to 0 or '' (a valid .env state,
| especially with account limits disabled) the page threw
| DivisionByZeroError/TypeError and 500'd — even though the view only shows the
| storage bar when enforce_account_limit is on.
|
*/

it('loads settings home when the account-size limit is zero', function () {
    config([
        'pixelfed.enforce_account_limit' => true,
        'pixelfed.max_account_size' => 0,
    ]);

    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->get('/settings/home')
        ->assertOk();
});

it('loads settings home when the account-size limit is an empty string', function () {
    config([
        'pixelfed.enforce_account_limit' => true,
        'pixelfed.max_account_size' => '',
    ]);

    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->get('/settings/home')
        ->assertOk();
});

it('loads settings home when account limits are disabled', function () {
    config([
        'pixelfed.enforce_account_limit' => false,
        'pixelfed.max_account_size' => 0,
    ]);

    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->get('/settings/home')
        ->assertOk();
});

it('shows the storage bar with a valid account-size limit', function () {
    config([
        'pixelfed.enforce_account_limit' => true,
        'pixelfed.max_account_size' => 1000000,
    ]);

    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->get('/settings/home')
        ->assertOk()
        ->assertSee('% used');
});
