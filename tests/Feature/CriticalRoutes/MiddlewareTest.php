<?php

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Middleware Behavior Tests
|--------------------------------------------------------------------------
|
| These tests verify that key middleware correctly gates access,
| redirects, and adds headers as expected.
|
*/

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->refresh();
});

test('admin middleware blocks non-admin users', function () {
    $this->actingAs($this->user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/i/admin/dashboard')
        ->assertRedirect(config('app.url'));
});

test('admin middleware allows admin users', function () {
    $admin = User::factory()->admin()->create();
    $admin->refresh();

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/i/admin/dashboard')
        ->assertStatus(200);
});

test('password confirmation middleware redirects without confirmation', function () {
    $this->actingAs($this->user)
        ->get('/settings/security')
        ->assertRedirect(route('password.confirm'));
});

test('password confirmation middleware allows with confirmed session', function () {
    $this->actingAs($this->user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/settings/security')
        ->assertStatus(200);
});

test('frame guard middleware adds X-Frame-Options header', function () {
    $response = $this->get('/login');

    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
});

test('unauthenticated user is redirected to login from protected routes', function () {
    $this->get('/settings/home')
        ->assertRedirect('/login');
});

test('unauthenticated user gets 401 on auth-only api routes', function () {
    $this->getJson('/api/v1/accounts/verify_credentials')
        ->assertUnauthorized();
});
