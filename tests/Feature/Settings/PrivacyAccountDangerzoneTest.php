<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Privacy "make account private" dangerzone round-trip
|--------------------------------------------------------------------------
|
| The modal posts via axios (X-Requested-With + JSON Accept), so a stale
| password confirmation yields a 423 JSON RequirePassword response, not a
| 302. The frontend handles 423 by routing to password.confirm with a
| redirect param, and confirmPassword must honor that param so the user
| round-trips back after confirming.
|
*/

it('returns 423 JSON for the XHR privacy request when password is unconfirmed', function () {
    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->withHeader('Accept', 'application/json, text/plain, */*')
        ->post(route('settings.privacy.account'), [
            'mode' => 'remove-all',
            'duration' => 60,
        ])
        ->assertStatus(423)
        ->assertJson(['message' => 'Password confirmation required.']);
});

it('honors a same-origin redirect param through the password confirmation round-trip', function () {
    $user = User::factory()->create(['password' => Hash::make('secret-password')]);
    $user->refresh();

    // Visiting the sudo screen with a redirect param records the intended URL.
    $this->actingAs($user)
        ->get(route('password.confirm', ['redirect' => '/settings/privacy']))
        ->assertOk();

    // Confirming the password returns the user to that intended URL.
    $this->actingAs($user)
        ->post(route('password.confirm'), ['password' => 'secret-password'])
        ->assertRedirect('/settings/privacy');
});

it('ignores an off-site redirect param to prevent open redirects', function () {
    $user = User::factory()->create(['password' => Hash::make('secret-password')]);
    $user->refresh();

    $this->actingAs($user)
        ->get(route('password.confirm', ['redirect' => 'https://evil.test/phish']))
        ->assertOk();

    // The off-site URL must not become the intended redirect.
    $this->actingAs($user)
        ->post(route('password.confirm'), ['password' => 'secret-password'])
        ->assertRedirect();

    expect(session('url.intended'))->not->toBe('https://evil.test/phish');
});
