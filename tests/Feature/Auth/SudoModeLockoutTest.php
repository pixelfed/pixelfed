<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Auth;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Sudo-mode (password confirmation) brute-force protection
|--------------------------------------------------------------------------
|
| POST /i/auth/sudo gates destructive settings behind a password re-entry.
| A refactor dropped both the rate limit and the attempt counter/lockout, so
| a stolen session could brute-force it freely. Failures must now be throttled
| AND, past a hard cap, force a logout + session invalidation; a correct
| password still confirms sudo mode.
|
*/

describe('attempt counter lockout', function () {
    // The route throttle (5/min) is exercised separately; here we isolate the
    // controller's per-session counter so throttle state does not interfere.
    beforeEach(function () {
        $this->withoutMiddleware(ThrottleRequests::class);
    });

    it('logs the session out after too many failed sudo confirmations', function () {
        $user = User::factory()->create(); // factory password is 'password'
        $user->refresh();

        $this->actingAs($user);

        $this->post('/i/auth/sudo', ['password' => 'wrong-1'])->assertRedirect();
        expect(Auth::check())->toBeTrue();

        $this->post('/i/auth/sudo', ['password' => 'wrong-2'])->assertRedirect();
        expect(Auth::check())->toBeTrue();

        // The capped attempt ends the session and bounces to login.
        $this->post('/i/auth/sudo', ['password' => 'wrong-3'])
            ->assertRedirect(route('login'));

        expect(Auth::check())->toBeFalse();
    });

    it('confirms the session with the correct password', function () {
        $user = User::factory()->create();
        $user->refresh();

        $response = $this->actingAs($user)
            ->post('/i/auth/sudo', ['password' => 'password']);

        $response->assertRedirect();
        expect($response->headers->get('Location'))->not->toBe(route('login'));
        expect(session()->has('auth.password_confirmed_at'))->toBeTrue();
    });

    it('resets the failure counter after a successful confirmation', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user);

        $this->post('/i/auth/sudo', ['password' => 'wrong'])->assertRedirect();
        $this->post('/i/auth/sudo', ['password' => 'wrong'])->assertRedirect();
        $this->post('/i/auth/sudo', ['password' => 'password'])->assertRedirect();

        expect(session()->has('sudoModeAttempts'))->toBeFalse();
        expect(Auth::check())->toBeTrue();

        // Counter reset: a single later failure does not immediately lock out.
        $this->post('/i/auth/sudo', ['password' => 'wrong'])->assertRedirect();
        expect(Auth::check())->toBeTrue();
    });
});

describe('rate limit', function () {
    it('throttles rapid repeated sudo confirmation attempts', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user);

        // 5 requests/min are allowed; the 6th within the window is throttled.
        // The lockout logs out at attempt 3, so re-auth between attempts to
        // keep hitting the endpoint until the throttle fires.
        $status = null;
        for ($i = 0; $i < 8; $i++) {
            $this->actingAs($user);
            $status = $this->post('/i/auth/sudo', ['password' => 'wrong'])->getStatusCode();
            if ($status === 429) {
                break;
            }
        }

        expect($status)->toBe(429);
    });
});
