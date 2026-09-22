<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Legacy /api/admin/config backward compatibility (Task 17)
|--------------------------------------------------------------------------
|
| The pre-existing admin config API (AdminApiController@getConfiguration /
| @updateConfiguration) must keep working after the config-cache refactor.
| The enable_cc gate (abort_unless(config('instance.enable_cc'), 400)) was
| REMOVED, so an authorized admin now gets a 200 unconditionally — these tests
| lock that in so a future change cannot silently regate the legacy surface.
|
| Routes (routes/api.php, unchanged):
|   GET  /api/admin/config          getConfiguration   (admin:read)
|   POST /api/admin/config/update   updateConfiguration (admin:write)
|
| Each item is { name, description, key, state } with state derived via
| config_cache($key). The 5 keys are all ENVCONFIG booleans; under
| .env.testing OPEN_REGISTRATION=true is present+valid, so open_registration is
| env-locked and put() no-ops — the endpoint still returns 200 + current state.
|
| Auth mirrors AdminAccessTest / ScopeTest: Passport::actingAs(admin, [scopes]);
| non-admin / wrong-scope → 404; unauthenticated → 401.
|
| Validates: Requirements 9.1, 9.3
|
*/

const LEGACY_KNOWN_KEY = 'pixelfed.open_registration';

function legacyAdmin(array $scopes): User
{
    $admin = User::factory()->admin()->create();
    $admin->refresh();
    Passport::actingAs($admin, $scopes);

    return $admin;
}

/*
|--------------------------------------------------------------------------
| GET /api/admin/config
|--------------------------------------------------------------------------
*/

test('GET /api/admin/config as admin returns the 5 config items with name/description/key/state (9.1, 9.3)', function () {
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

test('GET /api/admin/config is not gated by enable_cc: an admin always gets 200, never 400 (9.3)', function () {
    legacyAdmin(['admin:read']);

    $this->getJson('/api/admin/config')
        ->assertOk()
        ->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| POST /api/admin/config/update
|--------------------------------------------------------------------------
*/

test('POST /api/admin/config/update as admin returns 200 + the collection with config_cache-mapped state (9.1, 9.3)', function () {
    legacyAdmin(['admin:write']);

    // open_registration is ENVCONFIG and env-locked under .env.testing, so the
    // underlying put() no-ops; the endpoint still returns 200 with the current
    // effective state (it does NOT error). Assert 200 + shape, not a flip.
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

test('POST /api/admin/config/update with a disallowed key returns 400 (9.3)', function () {
    legacyAdmin(['admin:write']);

    $this->postJson('/api/admin/config/update', [
        'key' => 'this.key.is.not.allowed',
        'value' => true,
    ])->assertStatus(400);
});

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

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
