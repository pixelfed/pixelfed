<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/

it('renders the login page', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Forgot Password');
});

it('authenticates a user with valid credentials', function () {
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ])->assertRedirect('/i/web');

    $this->assertAuthenticatedAs($user);
});

it('rejects login with an incorrect password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('correct-password'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

it('rejects login with a non-existent email', function () {
    $this->post('/login', [
        'email' => 'nobody@example.com',
        'password' => 'any-password',
    ]);

    $this->assertGuest();
});

it('returns HTTP 429 after too many failed login attempts', function () {
    $user = User::factory()->create();

    // The Fortify `login` limiter allows 5 attempts per 60 minutes, keyed by
    // lowercase(email) . '|' . ip(). Exhaust the 5 allowed attempts, then the
    // 6th request from the same email/IP is throttled with HTTP 429.
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ]);
    }

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong',
    ])->assertStatus(429);
});

it('redirects authenticated users away from the login page', function () {
    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->get('/login')
        ->assertRedirect();
});

it('logs out an authenticated user', function () {
    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});
