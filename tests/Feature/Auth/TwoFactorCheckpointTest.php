<?php

use App\Models\AccountLog;
use App\Models\User;
use App\Services\PendingLoginService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use PragmaRX\Google2FA\Google2FA;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| 2FA checkpoint rate limiting + audit
|--------------------------------------------------------------------------
|
| The 2FA verify endpoint must be server-side rate limited (so re-login cannot
| reset an unlimited guess budget) and failed attempts must be audit-logged.
|
| The 2FA flow is a pending-login model: after valid credentials the user is
| NOT logged in but a pending session (auth.pending, step=2fa) is created, and
| the code is submitted to POST /login/2fa.
|
*/

/**
 * Seed a pending 2FA login session for the given user, mirroring what
 * PendingLoginService::start writes after credentials are verified.
 */
function pending2faSession(User $user, int $attempts = 0): array
{
    return [
        PendingLoginService::SESSION_KEY => [
            'user_id' => $user->id,
            'email' => $user->email,
            'remember' => false,
            'step' => PendingLoginService::STEP_2FA,
            'attempts' => $attempts,
            'expires_at' => now()->addSeconds(PendingLoginService::TTL_SECONDS)->getTimestamp(),
        ],
    ];
}

it('applies throttle middleware to the 2FA verify route', function () {
    $route = collect(Route::getRoutes())->first(function ($r) {
        return $r->uri() === 'login/2fa' && in_array('POST', $r->methods());
    });

    expect($route)->not->toBeNull();

    $hasThrottle = collect($route->gatherMiddleware())
        ->contains(fn ($m) => is_string($m) && str_starts_with($m, 'throttle'));

    expect($hasThrottle)->toBeTrue();
});

it('audit-logs a failed 2FA verification', function () {
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $user = User::factory()->create(['2fa_secret' => $secret, '2fa_enabled' => true]);
    $user->refresh();

    // A wrong 6-digit code against an active pending 2FA login.
    $this->withSession(pending2faSession($user))
        ->post('/login/2fa', ['code' => '000000']);

    expect(
        AccountLog::where('user_id', $user->id)
            ->where('action', 'auth.2fa.failed')
            ->exists()
    )->toBeTrue();
});
