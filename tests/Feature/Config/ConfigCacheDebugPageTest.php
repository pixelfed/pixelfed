<?php

use App\Http\Controllers\Api\v2026\Admin\ConfigCacheDiagnosticsController;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

const DEBUG_ENDPOINT = '/api/v2.1/admin/diagnostics/config-cache';
const CLEAR_ENDPOINT = '/api/v2.1/admin/diagnostics/config-cache/clear-cache';

test('an admin with a confirmed password can open the config-cache debug page', function () {
    $admin = User::factory()->admin()->create();
    $admin->refresh();

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('admin.config-cache'))
        ->assertOk()
        ->assertSee('Config Cache')
        ->assertSee('Sync Health')
        ->assertSee(DEBUG_ENDPOINT);
});

test('a non-admin is blocked from the config-cache debug page', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $user->refresh();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('admin.config-cache'))
        ->assertRedirect(config('app.url'));
});

test('the config-cache debug route is registered with the admin + dangerzone middleware', function () {
    $route = collect(Route::getRoutes())
        ->first(fn ($r) => $r->getName() === 'admin.config-cache');

    expect($route)->not->toBeNull();
    expect($route->getActionName())
        ->toBe(ConfigCacheDiagnosticsController::class.'@debugPage');

    $middleware = $route->gatherMiddleware();
    expect($middleware)->toContain('admin');
    expect($middleware)->toContain('dangerzone');
});

// --- JSON diagnostics endpoints ---

test('GET diagnostics returns rows + sync for an admin', function () {
    $admin = User::factory()->admin()->create()->fresh();
    Passport::actingAs($admin, ['admin:read']);

    $this->getJson(DEBUG_ENDPOINT)
        ->assertOk()
        ->assertJsonStructure([
            'rows' => [['match', 'key', 'env', 'list', 'source', 'locked', 'protected', 'effective', 'db', 'config']],
            'sync' => ['sync_hash', 'lock_held'],
        ]);
});

test('GET diagnostics for a non-admin token returns 404', function () {
    $user = User::factory()->create(['is_admin' => false])->fresh();
    Passport::actingAs($user, ['admin:read']);

    $this->getJson(DEBUG_ENDPOINT)->assertNotFound();
});

test('GET diagnostics unauthenticated returns 401', function () {
    $this->getJson(DEBUG_ENDPOINT)->assertUnauthorized();
});

test('POST clear-cache triggers the sync and returns a message for an admin', function () {
    $admin = User::factory()->admin()->create()->fresh();
    Passport::actingAs($admin, ['admin:write']);

    Cache::forget(\App\Services\ConfigCacheService::MARKER_KEY);

    $this->postJson(CLEAR_ENDPOINT)
        ->assertOk()
        ->assertJsonPath('message', 'Config cache reconciled and cleared.');

    // The forced reconcile writes the sync marker as a side effect.
    expect(Cache::get(\App\Services\ConfigCacheService::MARKER_KEY))->not->toBeNull();
});

test('POST clear-cache for a non-admin token returns 404', function () {
    $user = User::factory()->create(['is_admin' => false])->fresh();
    Passport::actingAs($user, ['admin:write']);

    $this->postJson(CLEAR_ENDPOINT)->assertNotFound();
});

test('POST clear-cache unauthenticated returns 401', function () {
    $this->postJson(CLEAR_ENDPOINT)->assertUnauthorized();
});
