<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PragmaRX\Google2FA\Google2FA;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| 2FA forced-logout session cleanup
|--------------------------------------------------------------------------
|
| When repeated failed 2FA attempts force a logout, the 2fa.session.active
| flag must be cleared. Otherwise it survives logout/login (session data is
| preserved across regenerate) and lets the next user skip 2FA on a shared
| session.
|
*/

beforeEach(function () {
    $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
});

it('clears the 2fa.session.active flag when forced logout occurs', function () {
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $user = User::factory()->create(['2fa_secret' => $secret, '2fa_enabled' => true]);
    $user->refresh();

    $this->actingAs($user)
        ->withSession([
            '2fa.attempts' => 3,
            '2fa.session.active' => [true],
        ])
        ->post('/i/auth/checkpoint', ['code' => '000000'])
        ->assertRedirect('/');

    // The forced logout must have cleared the 2FA session flag.
    expect(session()->has('2fa.session.active'))->toBeFalse();
    expect(session()->has('2fa.attempts'))->toBeFalse();
});
