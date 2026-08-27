<?php

use App\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Two-Factor Authentication
|--------------------------------------------------------------------------
*/

it('redirects when 2fa is enabled and session is unverified', function () {
    $user = User::factory()->create([
        '2fa_enabled' => true,
        '2fa_secret' => 'TESTSECRETKEY123',
    ]);
    $user->refresh();

    $this->actingAs($user)
        ->get('/settings/home')
        ->assertRedirect('/i/auth/checkpoint');
});

it('renders the 2fa checkpoint page for authenticated user', function () {
    $user = User::factory()->create([
        '2fa_enabled' => true,
        '2fa_secret' => 'TESTSECRETKEY123',
    ]);
    $user->refresh();

    $this->actingAs($user)
        ->get('/i/auth/checkpoint')
        ->assertOk();
});

it('renders the 2fa setup page behind password confirmation', function () {
    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/settings/security/2fa/setup')
        ->assertOk();
});

it('requires password confirmation to access 2fa setup', function () {
    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->get('/settings/security/2fa/setup')
        ->assertRedirect(route('password.confirm'));
});

it('requires password confirmation to access 2fa recovery codes', function () {
    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->get('/settings/security/2fa/recovery-codes')
        ->assertRedirect(route('password.confirm'));
});
