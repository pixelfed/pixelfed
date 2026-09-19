<?php

use App\Http\Controllers\Api\v2026\Admin\ConfigCacheDiagnosticsController;
use App\Models\ConfigCache as ConfigCacheModel;
use App\Models\User;
use App\Services\ConfigCacheService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Config Cache "Reconcile & Clear Cache" button — end-to-end (HTTP) tests
|--------------------------------------------------------------------------
|
| Exercises the full flow through the web layer:
|   POST i/admin/diagnostics/config-cache/clear-cache
|     → ConfigCache@clearCache
|     → Artisan::call('admin:pixelfed-config-cache-sync', ['--force' => true])
|     → reconcile() (prune stale/empty ENVBOUND rows, seed, overwrite drift)
|     → ConfigCacheService::flushAll() (drop every managed key's memoized cache)
|     → redirect back to admin.config-cache with a status flash.
|
| Route middleware ['admin', 'dangerzone'] mirrors the debug page: an admin
| with a confirmed password (session 'auth.password_confirmed_at') may POST.
|
| Env presence is driven like the other Config suites: mutate the process env
| across every source the Env repository reads, then reset the cached
| (immutable) repository via reflection so the next Env::get() re-reads.
|
| Real KEYS keys are reused so the tests track the live registry:
|   ENVBOUND    : filesystems.disks.s3.region  (AWS_DEFAULT_REGION)
|   ENVCONFIG: captcha.driver               (CAPTCHA_DRIVER)
|   ADMINONLY  : uikit.custom.css             (no env)
|   BOOLEAN    : autospam.nlp.enabled         (ADMINONLY, rule boolean)
*/

const E2E_ENVBOUND_KEY = 'filesystems.disks.s3.region';
const E2E_ENVBOUND_VAR = 'AWS_DEFAULT_REGION';

const E2E_ENVCONFIG_KEY = 'captcha.driver';
const E2E_ENVCONFIG_VAR = 'CAPTCHA_DRIVER';

const E2E_ADMINONLY_KEY = 'uikit.custom.css';

const E2E_BOOL_KEY = 'autospam.nlp.enabled';

const E2E_MARKER_KEY = 'config-cache:sync-hash';
const E2E_LOCK_KEY = 'config-cache:sync';

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

/** An admin acting with a confirmed password, ready to POST the dangerzone route. */
function e2eAdmin(): User
{
    $admin = User::factory()->admin()->create();
    $admin->refresh();

    return $admin;
}

/** POST the clear-cache route as a confirmed-password admin. */
function e2ePostClear($test, User $admin)
{
    // pixelfed enables CSRF protection in every environment via
    // preventRequestForgery() (bootstrap/app.php), so a test POST without a
    // real token 419s. Bypass the CSRF middleware — these tests exercise the
    // controller/reconcile flow, not token verification.
    return $test->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->withoutMiddleware(PreventRequestForgery::class)
        ->post(route('admin.config-cache.clear'));
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

/*
|--------------------------------------------------------------------------
| route + auth
|--------------------------------------------------------------------------
*/

test('the clear-cache route is registered as POST with admin + dangerzone middleware', function () {
    $route = collect(Route::getRoutes())
        ->first(fn ($r) => $r->getName() === 'admin.config-cache.clear');

    expect($route)->not->toBeNull();
    expect($route->methods())->toContain('POST');
    expect($route->getActionName())->toBe(ConfigCacheDiagnosticsController::class.'@clearCache');

    $middleware = $route->gatherMiddleware();
    expect($middleware)->toContain('admin');
    expect($middleware)->toContain('dangerzone');
});

test('an admin with a confirmed password can POST clear-cache and is redirected back with a status', function () {
    $admin = e2eAdmin();

    e2ePostClear($this, $admin)
        ->assertRedirect(route('admin.config-cache'))
        ->assertSessionHas('status');
});

test('a non-admin is blocked from the clear-cache route', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $user->refresh();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->withoutMiddleware(PreventRequestForgery::class)
        ->post(route('admin.config-cache.clear'))
        ->assertRedirect(config('app.url'));

    // No reconcile happened: no marker was written.
    expect(Cache::get(E2E_MARKER_KEY))->toBeNull();
});

/*
|--------------------------------------------------------------------------
| the button reconciles the DB (force, bypasses the hash gate)
|--------------------------------------------------------------------------
*/

test('clear-cache prunes a stale ENVBOUND row when the env value is empty (end to end)', function () {
    $admin = e2eAdmin();

    // Env present but resolves empty (env-authoritative), so the reconcile
    // prunes the stale row. Plant an orphaned row.
    e2eSetProcessEnv(E2E_ENVBOUND_VAR, 'us-east-1');
    Config::set(E2E_ENVBOUND_KEY, '');
    ConfigCacheModel::where('k', E2E_ENVBOUND_KEY)->delete();
    $row = new ConfigCacheModel;
    $row->k = E2E_ENVBOUND_KEY;
    $row->v = 'orphaned-encrypted-value';
    $row->save();
    expect(ConfigCacheModel::where('k', E2E_ENVBOUND_KEY)->exists())->toBeTrue();

    e2ePostClear($this, $admin)->assertRedirect(route('admin.config-cache'));

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

    e2ePostClear($this, $admin)->assertRedirect(route('admin.config-cache'));

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

    e2ePostClear($this, $admin)->assertRedirect(route('admin.config-cache'));

    // The last-known value survives — ENVCONFIG-env-absent is DB-authoritative.
    expect(ConfigCacheModel::where('k', E2E_ENVCONFIG_KEY)->value('v'))->toBe('last-known-turnstile');
});

/*
|--------------------------------------------------------------------------
| the button flushes the memoized cache so reads are correct afterward
|--------------------------------------------------------------------------
*/

test('clear-cache flushes a stale cached value for a key it does not touch (end to end)', function () {
    $admin = e2eAdmin();

    // ADMINONLY key with a stale memoized cache entry the reconcile won't touch.
    $adminCacheKey = ConfigCacheService::CACHE_KEY.E2E_ADMINONLY_KEY;
    Cache::forever($adminCacheKey, 'STALE-CACHED-CSS');
    expect(Cache::get($adminCacheKey))->toBe('STALE-CACHED-CSS');

    e2ePostClear($this, $admin)->assertRedirect(route('admin.config-cache'));

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

    e2ePostClear($this, $admin)->assertRedirect(route('admin.config-cache'));

    // Cache flushed → next read casts the DB "0" back to a real bool false.
    $val = ConfigCacheService::get(E2E_BOOL_KEY);
    expect($val)->toBeBool();
    expect($val)->toBeFalse();
});

test('the debug page renders the Reconcile & Clear Cache button', function () {
    $admin = e2eAdmin();

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('admin.config-cache'))
        ->assertOk()
        ->assertSee('Clear Cache')
        ->assertSee(route('admin.config-cache.clear'));
});
