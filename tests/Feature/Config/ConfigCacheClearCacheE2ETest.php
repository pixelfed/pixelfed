<?php

use App\Http\Controllers\Api\v2026\Admin\ConfigCacheDiagnosticsController;
use App\Models\ConfigCache as ConfigCacheModel;
use App\Models\User;
use App\Services\ConfigCacheService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

const E2E_ENVBOUND_KEY = 'filesystems.disks.s3.region';
const E2E_ENVBOUND_VAR = 'AWS_DEFAULT_REGION';

const E2E_ENVCONFIG_KEY = 'captcha.driver';
const E2E_ENVCONFIG_VAR = 'CAPTCHA_DRIVER';

const E2E_ADMINONLY_KEY = 'uikit.custom.css';

const E2E_BOOL_KEY = 'autospam.nlp.enabled';

const E2E_MARKER_KEY = 'config-cache:sync-hash';
const E2E_LOCK_KEY = 'config-cache:sync';

const E2E_CLEAR_ENDPOINT = '/api/v2.1/admin/diagnostics/config-cache/clear-cache';

function e2eResetEnvRepository(): void
{
    $ref = new ReflectionClass(Env::class);
    $prop = $ref->getProperty('repository');
    $prop->setAccessible(true);
    $prop->setValue(null, null);
}

function e2eSetProcessEnv(string $name, ?string $value): void
{
    if ($value === null) {
        putenv($name);
        unset($_ENV[$name], $_SERVER[$name]);
    } else {
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    e2eResetEnvRepository();
}

function e2eForget(string $key): void
{
    Cache::forget(ConfigCacheService::CACHE_KEY.$key);
}

// An admin authorized to call the admin:write diagnostics endpoint.
function e2eAdmin(): User
{
    $admin = User::factory()->admin()->create();
    $admin->refresh();

    return $admin;
}

// POST the clear-cache JSON endpoint as an admin with the admin:write scope.
function e2ePostClear($test, User $admin)
{
    Passport::actingAs($admin, ['admin:write']);

    return $test->postJson(E2E_CLEAR_ENDPOINT);
}

beforeEach(function () {
    $this->originalEnv = [
        E2E_ENVBOUND_VAR => getenv(E2E_ENVBOUND_VAR),
        E2E_ENVCONFIG_VAR => getenv(E2E_ENVCONFIG_VAR),
        'PIXELFED_CONFIG_CACHE_SYNC' => getenv('PIXELFED_CONFIG_CACHE_SYNC'),
    ];
    $this->originalConfig = [
        E2E_ENVBOUND_KEY => Config::get(E2E_ENVBOUND_KEY),
        E2E_ENVCONFIG_KEY => Config::get(E2E_ENVCONFIG_KEY),
        E2E_ADMINONLY_KEY => Config::get(E2E_ADMINONLY_KEY),
        E2E_BOOL_KEY => Config::get(E2E_BOOL_KEY),
    ];

    e2eSetProcessEnv(E2E_ENVBOUND_VAR, null);
    e2eSetProcessEnv(E2E_ENVCONFIG_VAR, null);
    e2eSetProcessEnv('PIXELFED_CONFIG_CACHE_SYNC', null);

    Cache::forget(E2E_MARKER_KEY);
    Cache::forget(E2E_LOCK_KEY);
    foreach ([E2E_ENVBOUND_KEY, E2E_ENVCONFIG_KEY, E2E_ADMINONLY_KEY, E2E_BOOL_KEY] as $key) {
        e2eForget($key);
    }
});

afterEach(function () {
    foreach ($this->originalEnv as $var => $value) {
        e2eSetProcessEnv($var, $value === false ? null : $value);
    }
    foreach ($this->originalConfig as $key => $value) {
        Config::set($key, $value);
    }
    e2eResetEnvRepository();

    Cache::forget(E2E_MARKER_KEY);
    Cache::forget(E2E_LOCK_KEY);
    foreach ([E2E_ENVBOUND_KEY, E2E_ENVCONFIG_KEY, E2E_ADMINONLY_KEY, E2E_BOOL_KEY] as $key) {
        e2eForget($key);
    }
});

test('the clear-cache route is registered as POST with auth:sanctum,api middleware', function () {
    $route = collect(Route::getRoutes())
        ->first(fn ($r) => $r->uri() === 'api/v2.1/admin/diagnostics/config-cache/clear-cache' && in_array('POST', $r->methods(), true));

    expect($route)->not->toBeNull();
    expect($route->getActionName())->toBe(ConfigCacheDiagnosticsController::class.'@clearCacheApi');

    $middleware = $route->gatherMiddleware();
    expect($middleware)->toContain('auth:sanctum,api');
});

test('an admin can POST clear-cache and gets a success message', function () {
    $admin = e2eAdmin();

    e2ePostClear($this, $admin)
        ->assertOk()
        ->assertJsonPath('message', 'Config cache reconciled and cleared.');
});

test('a non-admin is blocked from the clear-cache endpoint', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $user->refresh();
    Passport::actingAs($user, ['admin:write']);

    $this->postJson(E2E_CLEAR_ENDPOINT)->assertNotFound();

    // No reconcile happened: no marker was written.
    expect(Cache::get(E2E_MARKER_KEY))->toBeNull();
});

test('clear-cache prunes a stale ENVBOUND row when the env value is empty (end to end)', function () {
    $admin = e2eAdmin();

    // Env present but empty (env-authoritative) → the reconcile prunes the stale row.
    e2eSetProcessEnv(E2E_ENVBOUND_VAR, 'us-east-1');
    Config::set(E2E_ENVBOUND_KEY, '');
    ConfigCacheModel::where('k', E2E_ENVBOUND_KEY)->delete();
    $row = new ConfigCacheModel;
    $row->k = E2E_ENVBOUND_KEY;
    $row->v = 'orphaned-encrypted-value';
    $row->save();
    expect(ConfigCacheModel::where('k', E2E_ENVBOUND_KEY)->exists())->toBeTrue();

    e2ePostClear($this, $admin)->assertOk();

    // The button forces a reconcile which prunes the dead row.
    expect(ConfigCacheModel::where('k', E2E_ENVBOUND_KEY)->exists())->toBeFalse();
});

test('clear-cache overwrites a drifted ENVBOUND row back to the env value (end to end)', function () {
    $admin = e2eAdmin();

    e2eSetProcessEnv(E2E_ENVBOUND_VAR, 'us-east-1');
    Config::set(E2E_ENVBOUND_KEY, 'us-east-1');

    // A stale row that drifted from the env value.
    ConfigCacheService::putRaw(E2E_ENVBOUND_KEY, 'eu-west-9');
    expect(ConfigCacheModel::where('k', E2E_ENVBOUND_KEY)->value('v'))->toBe('eu-west-9');

    e2ePostClear($this, $admin)->assertOk();

    // Forced reconcile pulls the row back to the authoritative env value.
    expect(ConfigCacheModel::where('k', E2E_ENVBOUND_KEY)->value('v'))->toBe('us-east-1');
});

test('clear-cache preserves an ENVCONFIG row when the env var is deleted (DB keeps authority, end to end)', function () {
    $admin = e2eAdmin();

    e2eSetProcessEnv(E2E_ENVCONFIG_VAR, null);
    Config::set(E2E_ENVCONFIG_KEY, '');

    ConfigCacheModel::where('k', E2E_ENVCONFIG_KEY)->delete();
    $row = new ConfigCacheModel;
    $row->k = E2E_ENVCONFIG_KEY;
    $row->v = 'last-known-turnstile';
    $row->save();

    e2ePostClear($this, $admin)->assertOk();

    // The last-known value survives — ENVCONFIG-env-absent is DB-authoritative.
    expect(ConfigCacheModel::where('k', E2E_ENVCONFIG_KEY)->value('v'))->toBe('last-known-turnstile');
});

test('clear-cache flushes a stale cached value for a key it does not touch (end to end)', function () {
    $admin = e2eAdmin();

    // ADMINONLY key with a stale memoized cache entry the reconcile won't touch.
    $adminCacheKey = ConfigCacheService::CACHE_KEY.E2E_ADMINONLY_KEY;
    Cache::forever($adminCacheKey, 'STALE-CACHED-CSS');
    expect(Cache::get($adminCacheKey))->toBe('STALE-CACHED-CSS');

    e2ePostClear($this, $admin)->assertOk();

    // flushAll() cleared it; the next read will re-resolve from DB/config.
    expect(Cache::get($adminCacheKey))->toBeNull();
});

test('after clear-cache a boolean key re-resolves to a real bool, not a stale string (end to end)', function () {
    $admin = e2eAdmin();

    // Simulate the reported bug: config false, but a stale row/cache holds "0".
    Config::set(E2E_BOOL_KEY, false);
    ConfigCacheModel::where('k', E2E_BOOL_KEY)->delete();
    $row = new ConfigCacheModel;
    $row->k = E2E_BOOL_KEY;
    $row->v = '0';
    $row->save();
    // Poison the cache with the pre-fix string value.
    Cache::forever(ConfigCacheService::CACHE_KEY.E2E_BOOL_KEY, '0');

    e2ePostClear($this, $admin)->assertOk();

    // Cache flushed → next read casts the DB "0" back to a real bool false.
    $val = ConfigCacheService::get(E2E_BOOL_KEY);
    expect($val)->toBeBool();
    expect($val)->toBeFalse();
});

test('the debug page shows the clear button and the diagnostics endpoint', function () {
    $admin = e2eAdmin();

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('admin.config-cache'))
        ->assertOk()
        ->assertSee('Clear Cache')
        ->assertSee('/api/v2.1/admin/diagnostics/config-cache');
});
