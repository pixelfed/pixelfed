<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| API Scope & Authorization Tests
|--------------------------------------------------------------------------
*/

describe('unauthenticated requests', function () {
    it('returns 401 for protected endpoints without a token', function (string $endpoint) {
        $this->getJson($endpoint)
            ->assertUnauthorized();
    })->with([
        'verify credentials' => '/api/v1/accounts/verify_credentials',
        'home timeline' => '/api/v1/timelines/home',
        'notifications' => '/api/v1/notifications',
        'bookmarks' => '/api/v1/bookmarks',
        'favourites' => '/api/v1/favourites',
    ]);
});

describe('authenticated with read scope', function () {
    it('allows read access to account endpoints', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/accounts/verify_credentials')
            ->assertOk()
            ->assertJsonStructure(['id', 'username']);
    });

    it('allows read access to timelines', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/timelines/home')
            ->assertOk()
            ->assertJsonIsArray();

        $this->getJson('/api/v1/timelines/public')
            ->assertOk()
            ->assertJsonIsArray();
    });

    it('allows read access to notifications', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonIsArray();
    });
});

describe('public endpoints require no auth', function () {
    it('returns instance info without authentication', function () {
        $this->getJson('/api/v1/instance')
            ->assertOk()
            ->assertJsonStructure(['uri', 'title', 'description']);
    });

    it('returns custom emojis without authentication', function () {
        $this->getJson('/api/v1/custom_emojis')
            ->assertOk()
            ->assertJsonIsArray();
    });

    it('returns instance peers without authentication', function () {
        $this->getJson('/api/v1/instance/peers')
            ->assertOk()
            ->assertJsonIsArray();
    });

    it('accepts app registration without authentication', function () {
        $this->postJson('/api/v1/apps', [
            'client_name' => 'Test App',
            'redirect_uris' => 'urn:ietf:wg:oauth:2.0:oob',
            'scopes' => 'read',
        ])->assertOk()
            ->assertJsonStructure(['client_id', 'client_secret']);
    });
});

describe('admin scope', function () {
    it('returns 404 for admin endpoints with non-admin user', function () {
        // The admin API middleware returns 404 (not 403) for non-admin users
        // to avoid revealing the existence of admin endpoints.
        $user = User::factory()->create(['is_admin' => false]);
        $user->refresh();
        Passport::actingAs($user, ['admin:read']);

        $this->getJson('/api/admin/stats')
            ->assertNotFound();
    });

    it('allows admin endpoints for admin user with admin scope', function () {
        $user = User::factory()->admin()->create();
        $user->refresh();
        Passport::actingAs($user, ['admin:read']);

        $this->getJson('/api/admin/stats')
            ->assertOk();
    });
});
