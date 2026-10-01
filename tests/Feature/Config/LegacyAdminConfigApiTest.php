<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

const LEGACY_KNOWN_KEY = 'pixelfed.open_registration';

function legacyAdmin(array $scopes): User
{
    $admin = User::factory()->admin()->create();
    $admin->refresh();
    Passport::actingAs($admin, $scopes);

    return $admin;
}

test('GET /api/admin/config as admin returns the 5 config items with name/description/key/state', function () {
    legacyAdmin(['admin:read']);

    $response = $this->getJson('/api/admin/config')
        ->assertOk()
        ->assertJsonCount(5)
        ->assertJsonStructure([
            '*' => ['name', 'description', 'key', 'state'],
        ]);

    $keys = collect($response->json())->pluck('key')->all();
    expect($keys)->toContain(LEGACY_KNOWN_KEY);

    // state is derived via config_cache (a bool) for every item.
    foreach ($response->json() as $item) {
        expect($item['state'])->toBeBool();
    }
});

test('GET /api/admin/config: an admin always gets 200, never 400', function () {
    legacyAdmin(['admin:read']);

    $this->getJson('/api/admin/config')
        ->assertOk()
        ->assertStatus(200);
});

test('POST /api/admin/config/update as admin returns 200 + the collection with config_cache-mapped state', function () {
    legacyAdmin(['admin:write']);

    // open_registration is env-locked under .env.testing, so put() no-ops: still 200.
    $response = $this->postJson('/api/admin/config/update', [
        'key' => LEGACY_KNOWN_KEY,
        'value' => true,
    ])
        ->assertOk()
        ->assertJsonCount(5)
        ->assertJsonStructure([
            '*' => ['name', 'description', 'key', 'state'],
        ]);

    $item = collect($response->json())->firstWhere('key', LEGACY_KNOWN_KEY);
    expect($item)->not->toBeNull();
    expect($item['state'])->toBeBool();
});

test('POST /api/admin/config/update with a disallowed key returns 400', function () {
    legacyAdmin(['admin:write']);

    $this->postJson('/api/admin/config/update', [
        'key' => 'this.key.is.not.allowed',
        'value' => true,
    ])->assertStatus(400);
});

test('GET /api/admin/config for a non-admin (admin:read) returns 404', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $user->refresh();
    Passport::actingAs($user, ['admin:read']);

    $this->getJson('/api/admin/config')->assertNotFound();
});

test('POST /api/admin/config/update with an admin:read-only token returns 404 (needs admin:write)', function () {
    legacyAdmin(['admin:read']);

    $this->postJson('/api/admin/config/update', [
        'key' => LEGACY_KNOWN_KEY,
        'value' => true,
    ])->assertNotFound();
});

test('GET /api/admin/config unauthenticated returns 401', function () {
    $this->getJson('/api/admin/config')->assertUnauthorized();
});
