<?php

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| API Route Smoke Tests
|--------------------------------------------------------------------------
|
| These tests verify critical API endpoints return correct responses
| for both authenticated and unauthenticated requests. They catch
| routing errors, broken serialization, and auth guard issues.
|
*/

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->refresh();
});

test('api v1 verify credentials returns account for authed user', function () {
    Passport::actingAs($this->user, ['read']);

    $this->getJson('/api/v1/accounts/verify_credentials')
        ->assertStatus(200)
        ->assertJsonStructure(['id', 'username']);
});

test('api v1 home timeline returns array for authed user', function () {
    Passport::actingAs($this->user, ['read']);

    $this->getJson('/api/v1/timelines/home')
        ->assertStatus(200)
        ->assertJsonIsArray();
});

test('api v1 public timeline returns array for authed user', function () {
    Passport::actingAs($this->user, ['read']);

    $this->getJson('/api/v1/timelines/public')
        ->assertStatus(200)
        ->assertJsonIsArray();
});

test('api v1 custom emojis returns array', function () {
    $this->getJson('/api/v1/custom_emojis')
        ->assertStatus(200)
        ->assertJsonIsArray();
});

test('api v1 instance peers returns array', function () {
    $this->getJson('/api/v1/instance/peers')
        ->assertStatus(200)
        ->assertJsonIsArray();
});

test('api v1 notifications returns array for authed user', function () {
    Passport::actingAs($this->user, ['read']);

    $this->getJson('/api/v1/notifications')
        ->assertStatus(200)
        ->assertJsonIsArray();
});

test('unauthenticated api requests return 401', function () {
    $this->getJson('/api/v1/accounts/verify_credentials')
        ->assertUnauthorized();

    $this->getJson('/api/v1/timelines/home')
        ->assertUnauthorized();

    $this->getJson('/api/v1/notifications')
        ->assertUnauthorized();
});
