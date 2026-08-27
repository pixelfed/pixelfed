<?php

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Authenticated Route Smoke Tests
|--------------------------------------------------------------------------
|
| These tests verify that routes requiring authentication return expected
| status codes for logged-in users. They catch broken views, missing
| controllers, and auth middleware regressions.
|
*/

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->refresh();
    $this->profile = $this->user->profile;
});

test('authenticated user homepage redirects to spa', function () {
    $this->actingAs($this->user)
        ->get('/')
        ->assertRedirect('/i/web');
});

test('settings home page loads for authenticated user', function () {
    $this->actingAs($this->user)
        ->get('/settings/home')
        ->assertStatus(200);
});

test('settings privacy page loads', function () {
    $this->actingAs($this->user)
        ->get('/settings/privacy')
        ->assertStatus(200);
});

test('settings security page requires password confirmation', function () {
    $this->actingAs($this->user)
        ->get('/settings/security')
        ->assertRedirect(route('password.confirm'));
});

test('settings security page loads after password confirmation', function () {
    $this->actingAs($this->user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/settings/security')
        ->assertStatus(200);
});

test('discover page loads for authenticated user', function () {
    $this->actingAs($this->user)
        ->get('/discover')
        ->assertStatus(200);
});

test('notifications page loads', function () {
    $this->actingAs($this->user)
        ->get('/account/activity')
        ->assertStatus(200);
});

test('compose page loads', function () {
    $this->actingAs($this->user)
        ->get('/i/compose')
        ->assertStatus(200);
});

test('settings developers page requires password confirmation', function () {
    $this->actingAs($this->user)
        ->get('/settings/applications')
        ->assertRedirect(route('password.confirm'));
});

test('settings developers page loads after password confirmation', function () {
    $this->actingAs($this->user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/settings/applications')
        ->assertStatus(200);
});

test('oauth clients endpoint fails due to legacy route syntax', function () {
    // BUG: The oauth routes use legacy array 'uses' syntax which causes
    // ReflectionFunction TypeError in Laravel 12 + Livewire.
    // Fix: fix/oauth-route-syntax branch converts to modern fluent syntax.
    $this->actingAs($this->user, 'web')
        ->getJson('/oauth/clients')
        ->assertStatus(500);
})->group('known-bugs');

test('oauth personal access tokens fails due to legacy route syntax', function () {
    // BUG: Same as above - legacy route syntax causes ReflectionFunction TypeError.
    $this->actingAs($this->user, 'web')
        ->getJson('/oauth/personal-access-tokens')
        ->assertStatus(500);
})->group('known-bugs');

test('direct messages page loads', function () {
    $this->actingAs($this->user)
        ->get('/account/direct')
        ->assertStatus(200);
});

test('unauthenticated user cannot access settings', function () {
    $this->get('/settings/home')
        ->assertRedirect('/login');
});

test('unauthenticated user cannot access compose', function () {
    $this->get('/i/compose')
        ->assertStatus(403);
});
