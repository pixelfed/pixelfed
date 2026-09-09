<?php

use App\Models\AccountLog;
use App\Models\User;
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
*/

it('applies throttle middleware to the 2FA verify route', function () {
    $route = collect(Route::getRoutes())->first(function ($r) {
        return $r->uri() === 'i/auth/checkpoint' && in_array('POST', $r->methods());
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

    $this->actingAs($user)
        ->post('/i/auth/checkpoint', ['code' => '000000']);

    expect(
        AccountLog::where('user_id', $user->id)
            ->where('action', 'auth.2fa.failed')
            ->exists()
    )->toBeTrue();
});
