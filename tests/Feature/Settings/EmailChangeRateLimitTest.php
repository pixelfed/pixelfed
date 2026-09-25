<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| POST /settings/email must be rate limited
|--------------------------------------------------------------------------
|
| emailUpdate auto-dispatches a ConfirmEmail on every address change. Without
| a route throttle (and with the service cooldown keyed on email), an attacker
| could mail-bomb arbitrary inboxes by posting a fresh address each request.
| The route must cap sends like its resend sibling (throttle:3,10).
|
*/

beforeEach(function () {
    // Start from a clean limiter bucket so throttle state from earlier tests
    // in the shared-process suite cannot pre-consume this test's budget.
    Cache::flush();
    config(['pixelfed.enforce_email_verification' => true]);
    config(['instance.enable_cc' => false]);
    Mail::fake();
});

function confirmedSudo(): array
{
    return ['auth.password_confirmed_at' => time()];
}

it('throttles repeated email changes to distinct addresses', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->refresh();

    // 3 changes allowed per 10 minutes.
    for ($i = 1; $i <= 3; $i++) {
        $this->actingAs($user)
            ->withSession(confirmedSudo())
            ->post('/settings/email', ['email' => "victim{$i}@example.test"])
            ->assertStatus(302);
    }

    // The 4th change within the window must be throttled.
    $this->actingAs($user)
        ->withSession(confirmedSudo())
        ->post('/settings/email', ['email' => 'victim4@example.test'])
        ->assertStatus(429);
});
