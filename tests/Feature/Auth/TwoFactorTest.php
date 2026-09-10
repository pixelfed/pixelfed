<?php

use App\Models\User;
use App\Services\PendingLoginService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Two-Factor Authentication
|--------------------------------------------------------------------------
|
| 2FA uses a pending-login model: valid credentials for a 2FA-enabled user do
| NOT authenticate the session. Instead a pending login (auth.pending) is
| created and the user is sent to the challenge at /login?step=2fa, where a
| code is submitted to POST /login/2fa.
|
*/

it('redirects a 2fa user to the challenge step after valid credentials', function () {
    $user = User::factory()->create([
        '2fa_enabled' => true,
        '2fa_secret' => 'TESTSECRETKEY123',
    ]);
    $user->refresh();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('login', ['step' => PendingLoginService::STEP_2FA]));

    // Credentials verified but the session is NOT yet authenticated.
    $this->assertGuest();

    // A pending 2FA login was recorded.
    expect(session(PendingLoginService::SESSION_KEY.'.step'))
        ->toBe(PendingLoginService::STEP_2FA);
});

it('renders the 2fa challenge page for a pending login', function () {
    $user = User::factory()->create([
        '2fa_enabled' => true,
        '2fa_secret' => 'TESTSECRETKEY123',
    ]);
    $user->refresh();

    $this->withSession([
        PendingLoginService::SESSION_KEY => [
            'user_id' => $user->id,
            'email' => $user->email,
            'remember' => false,
            'step' => PendingLoginService::STEP_2FA,
            'attempts' => 0,
            'expires_at' => now()->addSeconds(PendingLoginService::TTL_SECONDS)->getTimestamp(),
        ],
    ])
        ->get(route('login', ['step' => PendingLoginService::STEP_2FA]))
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
