<?php

use App\Models\User;
use App\Services\PendingLoginService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use PragmaRX\Google2FA\Google2FA;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| 2FA forced-logout / pending-session cleanup
|--------------------------------------------------------------------------
|
| When repeated failed 2FA attempts hit the limit, the pending login state
| (auth.pending) must be discarded so a fresh sign-in is required. Otherwise a
| stale pending session could let a subsequent request resume the challenge.
|
*/

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
});

it('clears the pending login state after the final failed attempt', function () {
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $user = User::factory()->create(['2fa_secret' => $secret, '2fa_enabled' => true]);
    $user->refresh();

    // Already at MAX_2FA_ATTEMPTS - 1 failures; the next wrong code trips the limit.
    $attempts = PendingLoginService::MAX_2FA_ATTEMPTS - 1;

    $this->withSession([
        PendingLoginService::SESSION_KEY => [
            'user_id' => $user->id,
            'email' => $user->email,
            'remember' => false,
            'step' => PendingLoginService::STEP_2FA,
            'attempts' => $attempts,
            'expires_at' => now()->addSeconds(PendingLoginService::TTL_SECONDS)->getTimestamp(),
        ],
    ])
        ->post('/login/2fa', ['code' => '000000'])
        ->assertRedirect(route('login'));

    // The pending login must have been discarded, forcing a fresh sign-in.
    expect(session()->has(PendingLoginService::SESSION_KEY))->toBeFalse();

    // And the user is not authenticated.
    $this->assertGuest();
});
