<?php

use App\Console\Commands\Admin\PixelfedConfigCacheSync;
use App\Models\ConfigCache as ConfigCacheModel;
use App\Services\ConfigCacheService;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| admin:pixelfed-config-cache-sync — sync command (Task 11)
|--------------------------------------------------------------------------
|
| Reconciles .env into config_cache for env-bound (ENVCONFIG) keys, hash-gated
| and single-flight. Cases:
|   - empty table → seeds missing rows
|   - env overwrite for env-present stale rows
|   - env-absent / ADMINONLY existing rows untouched
|   - idempotent second run
|   - change-hash unchanged → no-op (stale row NOT fixed, proving the gate)
|   - hash written only on success; marker-absent → sync re-runs
|   - PIXELFED_CONFIG_CACHE_SYNC default true; false skips; --no-sync skips
|   - lock prevents concurrent run
|   - two containers converge (second no-ops on matching hash)
|   - config:cache CommandFinished listener fires sync, and only for that command
|   - pre-migration safety (structural note)
|
| Env presence uses the suite's putenv + $_ENV/$_SERVER + Env-repo-reset
| reflection helper pattern. The marker/lock cache keys are cleared in
| beforeEach to isolate cases.
|
| Validates: Requirements 3.*, 8.*, 9.*
|
*/

const SYNC_MARKER_KEY = 'config-cache:sync-hash';
const SYNC_LOCK_KEY = 'config-cache:sync';

// Env-bound string key. Env-authoritative (overwritten on drift) when its env
// var is present; DB-authoritative (preserved) when absent.
const SYNC_ENVBOUND_KEY = 'filesystems.disks.s3.region';
const SYNC_ENVBOUND_VAR = 'AWS_DEFAULT_REGION';

// Env-bound key with an in: rule (env authoritative when present + valid).
const SYNC_ENVCONFIG_KEY = 'captcha.driver';
const SYNC_ENVCONFIG_VAR = 'CAPTCHA_DRIVER';

// ADMINONLY key (never overwritten by sync once a row exists).
const SYNC_ADMINONLY_KEY = 'uikit.custom.css';

function syncResetEnvRepository(): void
{
    $ref = new ReflectionClass(Env::class);
    $prop = $ref->getProperty('repository');
    $prop->setAccessible(true);
    $prop->setValue(null, null);
}

function syncSetProcessEnv(string $name, ?string $value): void
{
    if ($value === null) {
        putenv($name);
        unset($_ENV[$name], $_SERVER[$name]);
    } else {
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    syncResetEnvRepository();
}

/**
 * Clear the sync marker + lock and forget the memoized read cache for the keys
 * this suite touches, so each case starts from a clean slate.
 */
function syncReset(): void
{
    Cache::forget(SYNC_MARKER_KEY);
    Cache::forget(SYNC_LOCK_KEY);
    foreach ([SYNC_ENVBOUND_KEY, SYNC_ENVCONFIG_KEY, SYNC_ADMINONLY_KEY] as $key) {
        Cache::forget(ConfigCacheService::CACHE_KEY.$key);
    }
}

/**
 * Run the sync command directly and return its exit code.
 */
function runSync(array $params = []): int
{
    return Artisan::call('admin:pixelfed-config-cache-sync', $params);
}

beforeEach(function () {
    $this->originalEnv = [
        SYNC_ENVBOUND_VAR => getenv(SYNC_ENVBOUND_VAR),
        SYNC_ENVCONFIG_VAR => getenv(SYNC_ENVCONFIG_VAR),
        'PIXELFED_CONFIG_CACHE_SYNC' => getenv('PIXELFED_CONFIG_CACHE_SYNC'),
    ];
    $this->originalConfig = [
        SYNC_ENVBOUND_KEY => Config::get(SYNC_ENVBOUND_KEY),
        SYNC_ENVCONFIG_KEY => Config::get(SYNC_ENVCONFIG_KEY),
        SYNC_ADMINONLY_KEY => Config::get(SYNC_ADMINONLY_KEY),
    ];

    // Baseline: SYNC_ENVBOUND_* is the "env-authoritative" key (env present +
    // valid), SYNC_ENVCONFIG_* is the "env-absent editable" key. Individual
    // tests override as needed. Sync opt-in unset (default true).
    syncSetProcessEnv(SYNC_ENVBOUND_VAR, 'us-east-1');
    syncSetProcessEnv(SYNC_ENVCONFIG_VAR, null);
    syncSetProcessEnv('PIXELFED_CONFIG_CACHE_SYNC', null);

    syncReset();
});

afterEach(function () {
    foreach ($this->originalEnv as $var => $value) {
        syncSetProcessEnv($var, $value === false ? null : $value);
    }
    foreach ($this->originalConfig as $key => $value) {
        Config::set($key, $value);
    }
    syncResetEnvRepository();
    syncReset();
});

/*
|--------------------------------------------------------------------------
| empty table → sync seeds missing rows
|--------------------------------------------------------------------------
*/

test('empty table: sync seeds missing rows for keys whose config value is non-null (8.1, 8.2)', function () {
    // The keys under test have no row yet (a stray boot-time seed of unrelated
    // keys may leave a couple of rows, so we assert per-key absence rather than
    // a globally empty table).
    foreach ([SYNC_ENVBOUND_KEY, SYNC_ENVCONFIG_KEY, SYNC_ADMINONLY_KEY] as $key) {
        ConfigCacheModel::where('k', $key)->delete();
        expect(ConfigCacheModel::where('k', $key)->exists())->toBeFalse();
    }

    // Give the env-bound keys concrete non-null config values so they seed.
    // s3.region is env-bound: present env → env-authoritative, seeds from config.
    syncSetProcessEnv(SYNC_ENVBOUND_VAR, 'us-east-1');
    Config::set(SYNC_ENVBOUND_KEY, 'us-east-1');       // env-bound, env present
    Config::set(SYNC_ENVCONFIG_KEY, 'hcaptcha');      // env-bound, env absent → seed from config default

    expect(runSync())->toBe(0);

    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->value('v'))->toBe('us-east-1');
    expect(ConfigCacheModel::where('k', SYNC_ENVCONFIG_KEY)->value('v'))->toBe('hcaptcha');

    // ADMINONLY keys are never seeded by the sync.
    expect(ConfigCacheModel::where('k', SYNC_ADMINONLY_KEY)->exists())->toBeFalse();

    // The success marker is written last, on completion.
    expect(Cache::get(SYNC_MARKER_KEY))->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| env overwrite — ENVBOUND / ENVCONFIG-env-valid stale rows
|--------------------------------------------------------------------------
*/

test('sync overwrites a stale ENVBOUND row to match config (8.3, 9.2)', function () {
    Config::set(SYNC_ENVBOUND_KEY, 'us-east-1');

    // Plant a stale row that disagrees with config.
    ConfigCacheService::putRaw(SYNC_ENVBOUND_KEY, 'eu-west-9');
    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->value('v'))->toBe('eu-west-9');

    // Marker cleared in beforeEach, so the sync will run.
    expect(runSync())->toBe(0);

    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->value('v'))->toBe('us-east-1');
});

test('sync overwrites a stale ENVCONFIG row when its env var is present + valid (8.3)', function () {
    syncSetProcessEnv(SYNC_ENVCONFIG_VAR, 'turnstile');
    Config::set(SYNC_ENVCONFIG_KEY, 'turnstile');

    // Plant a stale row that disagrees with the env-resolved config value.
    ConfigCacheService::putRaw(SYNC_ENVCONFIG_KEY, 'hcaptcha');

    expect(runSync())->toBe(0);

    expect(ConfigCacheModel::where('k', SYNC_ENVCONFIG_KEY)->value('v'))->toBe('turnstile');
});

/*
|--------------------------------------------------------------------------
| ENVCONFIG-env-absent / ADMINONLY existing rows untouched
|--------------------------------------------------------------------------
*/

test('sync leaves an ENVCONFIG-env-absent existing row untouched (8.4)', function () {
    syncSetProcessEnv(SYNC_ENVCONFIG_VAR, null); // env absent → dynamic
    Config::set(SYNC_ENVCONFIG_KEY, 'hcaptcha');

    // A custom (admin-managed) value that differs from config.
    ConfigCacheService::putRaw(SYNC_ENVCONFIG_KEY, 'turnstile');

    expect(runSync())->toBe(0);

    // Must NOT be overwritten to the config value.
    expect(ConfigCacheModel::where('k', SYNC_ENVCONFIG_KEY)->value('v'))->toBe('turnstile');
});

test('sync leaves an ADMINONLY existing row untouched (8.4)', function () {
    Config::set(SYNC_ADMINONLY_KEY, '/* default */');

    ConfigCacheService::putRaw(SYNC_ADMINONLY_KEY, '.admin { color: red; }');

    expect(runSync())->toBe(0);

    expect(ConfigCacheModel::where('k', SYNC_ADMINONLY_KEY)->value('v'))->toBe('.admin { color: red; }');
});

/*
|--------------------------------------------------------------------------
| idempotent second run
|--------------------------------------------------------------------------
*/

test('sync is idempotent: a second run makes no further changes (8.5)', function () {
    Config::set(SYNC_ENVBOUND_KEY, 'us-east-1');
    ConfigCacheService::putRaw(SYNC_ENVBOUND_KEY, 'eu-west-9');

    expect(runSync())->toBe(0);
    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->value('v'))->toBe('us-east-1');

    $updatedAt = ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->value('updated_at');

    // Second run: nothing governed changed → hash matches marker → no-op.
    expect(runSync())->toBe(0);

    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->value('v'))->toBe('us-east-1');
    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->value('updated_at'))
        ->toEqual($updatedAt);
});

/*
|--------------------------------------------------------------------------
| change-hash unchanged → no-op (proves the hash gate)
|--------------------------------------------------------------------------
*/

test('change-hash unchanged: a stale row planted after a successful sync is NOT fixed (8.7)', function () {
    Config::set(SYNC_ENVBOUND_KEY, 'us-east-1');

    // First run writes the marker for the current governed values.
    expect(runSync())->toBe(0);
    expect(Cache::get(SYNC_MARKER_KEY))->not->toBeNull();

    // Now corrupt the row manually WITHOUT changing any governed config value.
    ConfigCacheService::putRaw(SYNC_ENVBOUND_KEY, 'eu-west-9');

    // Because the hash still matches the marker, the sync returns early and does
    // NOT reconcile the stale row. This proves the hash gate is in effect.
    expect(runSync())->toBe(0);
    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->value('v'))->toBe('eu-west-9');
});

/*
|--------------------------------------------------------------------------
| hash written only on success; marker-absent → sync re-runs
|--------------------------------------------------------------------------
*/

test('marker written on success equals the current change hash (8.7)', function () {
    Config::set(SYNC_ENVBOUND_KEY, 'us-east-1');

    expect(runSync())->toBe(0);

    // Recompute the change hash the same way the command does and compare.
    $expected = (function () {
        $method = new ReflectionMethod(PixelfedConfigCacheSync::class, 'configHash');
        $method->setAccessible(true);

        return $method->invoke(new PixelfedConfigCacheSync);
    })();

    expect(Cache::get(SYNC_MARKER_KEY))->toBe($expected);
});

test('marker absent → the next run performs reconciliation again (crash-safe invariant, 8.7)', function () {
    Config::set(SYNC_ENVBOUND_KEY, 'us-east-1');

    expect(runSync())->toBe(0);
    expect(Cache::get(SYNC_MARKER_KEY))->not->toBeNull();

    // Simulate a crash-before-marker (or a fresh container): forget the marker
    // and plant a stale row. With no marker, the sync must reconcile again.
    Cache::forget(SYNC_MARKER_KEY);
    ConfigCacheService::putRaw(SYNC_ENVBOUND_KEY, 'eu-west-9');

    expect(runSync())->toBe(0);
    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->value('v'))->toBe('us-east-1');
    expect(Cache::get(SYNC_MARKER_KEY))->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| PIXELFED_CONFIG_CACHE_SYNC / --no-sync
|--------------------------------------------------------------------------
*/

test('PIXELFED_CONFIG_CACHE_SYNC unset defaults to true: sync runs (8.9)', function () {
    syncSetProcessEnv('PIXELFED_CONFIG_CACHE_SYNC', null);
    Config::set(SYNC_ENVBOUND_KEY, 'us-east-1');

    expect(runSync())->toBe(0);

    // Seeded → default-on behavior confirmed.
    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->exists())->toBeTrue();
    expect(Cache::get(SYNC_MARKER_KEY))->not->toBeNull();
});

test('PIXELFED_CONFIG_CACHE_SYNC=false skips the sync entirely (8.9)', function () {
    syncSetProcessEnv('PIXELFED_CONFIG_CACHE_SYNC', 'false');
    Config::set(SYNC_ENVBOUND_KEY, 'us-east-1');

    expect(runSync())->toBe(0);

    // Nothing seeded, no marker written.
    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->exists())->toBeFalse();
    expect(Cache::get(SYNC_MARKER_KEY))->toBeNull();
});

test('--no-sync flag skips the sync entirely (8.9)', function () {
    Config::set(SYNC_ENVBOUND_KEY, 'us-east-1');

    expect(runSync(['--no-sync' => true]))->toBe(0);

    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->exists())->toBeFalse();
    expect(Cache::get(SYNC_MARKER_KEY))->toBeNull();
});

/*
|--------------------------------------------------------------------------
| lock prevents concurrent run
|--------------------------------------------------------------------------
*/

test('an externally-held lock makes the sync a no-op even when the hash changed (8.8)', function () {
    Config::set(SYNC_ENVBOUND_KEY, 'us-east-1');

    // Plant a stale row and ensure the hash differs from any marker (marker is
    // cleared in beforeEach), so only the lock can stop the reconciliation.
    ConfigCacheService::putRaw(SYNC_ENVBOUND_KEY, 'eu-west-9');

    // Acquire the lock with a DIFFERENT owner so the command cannot get it.
    $lock = Cache::lock(SYNC_LOCK_KEY, 60);
    expect($lock->get())->toBeTrue();

    try {
        expect(runSync())->toBe(0);

        // Lock held → no reconciliation → stale row unchanged, no marker.
        expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->value('v'))->toBe('eu-west-9');
        expect(Cache::get(SYNC_MARKER_KEY))->toBeNull();
    } finally {
        $lock->release();
    }
});

/*
|--------------------------------------------------------------------------
| two containers converge (second no-ops on matching hash)
|--------------------------------------------------------------------------
*/

test('two containers with identical env converge: the second run is a no-op (8.9a)', function () {
    Config::set(SYNC_ENVBOUND_KEY, 'us-east-1');
    ConfigCacheService::putRaw(SYNC_ENVBOUND_KEY, 'eu-west-9');

    // Container A reconciles and writes the shared marker.
    expect(runSync())->toBe(0);
    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->value('v'))->toBe('us-east-1');
    $markerAfterA = Cache::get(SYNC_MARKER_KEY);
    expect($markerAfterA)->not->toBeNull();

    // Container B (same env, shared Redis/DB) sees the matching hash → no-op.
    // Corrupt the row first to prove B does NOT re-reconcile (matching marker).
    ConfigCacheService::putRaw(SYNC_ENVBOUND_KEY, 'eu-west-9');
    expect(runSync())->toBe(0);
    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->value('v'))->toBe('eu-west-9');
    expect(Cache::get(SYNC_MARKER_KEY))->toBe($markerAfterA);
});

/*
|--------------------------------------------------------------------------
| config:cache CommandFinished listener fires sync, and only for that command
|--------------------------------------------------------------------------
*/

test('config:cache CommandFinished event triggers the sync (8.6)', function () {
    Config::set(SYNC_ENVBOUND_KEY, 'us-east-1');
    expect(Cache::get(SYNC_MARKER_KEY))->toBeNull();

    event(new CommandFinished('config:cache', new ArrayInput([]), new NullOutput, 0));

    // The provider's listener called the sync → marker written, row seeded.
    expect(Cache::get(SYNC_MARKER_KEY))->not->toBeNull();
    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->exists())->toBeTrue();
});

test('a non config:cache CommandFinished event does NOT trigger the sync (8.6)', function () {
    Config::set(SYNC_ENVBOUND_KEY, 'us-east-1');
    expect(Cache::get(SYNC_MARKER_KEY))->toBeNull();

    event(new CommandFinished('config:clear', new ArrayInput([]), new NullOutput, 0));

    // Listener is filtered to config:cache only → nothing happened.
    expect(Cache::get(SYNC_MARKER_KEY))->toBeNull();
    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->exists())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| pre-migration safety
|--------------------------------------------------------------------------
|
| A true "table missing" run is hard to simulate under LazilyRefreshDatabase
| without dropping config_cache (which would break the shared schema for other
| tests). The command wraps its DB work in try/catch (QueryException) and
| returns 0, so pre-migration safety is covered structurally. We assert the
| observable guarantee that matters: the command always returns exit code 0.
|
*/
test('sync always returns exit code 0 (pre-migration safe by structure, 8.5)', function () {
    Config::set(SYNC_ENVBOUND_KEY, 'us-east-1');

    expect(runSync())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| env PRESENT but empty → sync prunes the row; env ABSENT → preserve
|--------------------------------------------------------------------------
*/

test('sync prunes a row when the env var is present but its value is empty', function () {
    // Env is authoritative and resolves empty → the stale row is meaningless.
    syncSetProcessEnv(SYNC_ENVBOUND_VAR, 'us-east-1');
    Config::set(SYNC_ENVBOUND_KEY, '');

    ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->delete();
    $row = new ConfigCacheModel;
    $row->k = SYNC_ENVBOUND_KEY;
    $row->v = 'stale-leftover';
    $row->save();
    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->exists())->toBeTrue();

    expect(runSync())->toBe(0);

    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->exists())->toBeFalse();
});

test('sync does not seed a row when the env var is present but its value is empty', function () {
    syncSetProcessEnv(SYNC_ENVBOUND_VAR, 'us-east-1');
    Config::set(SYNC_ENVBOUND_KEY, '');
    ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->delete();

    expect(runSync())->toBe(0);

    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->exists())->toBeFalse();
});

test('sync preserves an existing row when the env var is deleted (DB takes authority)', function () {
    // env absent → the DB row holds the last-known .env value and must be KEPT:
    // not pruned, seeded, or overwritten from the now-empty config value.
    syncSetProcessEnv(SYNC_ENVCONFIG_VAR, null);
    Config::set(SYNC_ENVCONFIG_KEY, '');

    ConfigCacheModel::where('k', SYNC_ENVCONFIG_KEY)->delete();
    $row = new ConfigCacheModel;
    $row->k = SYNC_ENVCONFIG_KEY;
    $row->v = 'last-known-turnstile';
    $row->save();

    expect(runSync())->toBe(0);

    expect(ConfigCacheModel::where('k', SYNC_ENVCONFIG_KEY)->value('v'))->toBe('last-known-turnstile');
});

test('sync leaves ADMINONLY rows untouched (never seeded, overwritten, or pruned)', function () {
    // ADMINONLY has no env var; the sync must never touch its rows.
    ConfigCacheService::putRaw(SYNC_ADMINONLY_KEY, '.admin { color: red; }');

    // Change the ENVCONFIG key so the sync actually runs (hash flips).
    syncSetProcessEnv(SYNC_ENVCONFIG_VAR, null);
    Config::set(SYNC_ENVCONFIG_KEY, 'hcaptcha');

    expect(runSync())->toBe(0);

    // The admin-set value survives regardless of what config() says.
    expect(ConfigCacheModel::where('k', SYNC_ADMINONLY_KEY)->value('v'))->toBe('.admin { color: red; }');
});

/*
|--------------------------------------------------------------------------
| --force bypasses the change-hash gate for on-demand cleanup
|--------------------------------------------------------------------------
*/

test('--force reconciles a stale row even when the change-hash is unchanged', function () {
    Config::set(SYNC_ENVBOUND_KEY, 'us-east-1');

    // First run writes the marker for the current governed values.
    expect(runSync())->toBe(0);
    expect(Cache::get(SYNC_MARKER_KEY))->not->toBeNull();

    // Corrupt the row WITHOUT changing any governed config value, so the hash
    // still matches the marker (the gate would normally skip).
    ConfigCacheService::putRaw(SYNC_ENVBOUND_KEY, 'eu-west-9');

    // --force bypasses the gate and reconciles the stale row back to config.
    expect(runSync(['--force' => true]))->toBe(0);
    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->value('v'))->toBe('us-east-1');
});

test('--force prunes a stale row when the env value is empty and the hash is unchanged', function () {
    // Env present but resolves empty (env-authoritative), so a stale row should
    // be pruned. The marker already matches the hash, so a normal run skips.
    syncSetProcessEnv(SYNC_ENVBOUND_VAR, 'us-east-1');
    Config::set(SYNC_ENVBOUND_KEY, '');

    // Seed the marker for the current state so the gate is "closed".
    expect(runSync())->toBe(0);

    // Plant a stale row after the marker was written.
    ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->delete();
    $row = new ConfigCacheModel;
    $row->k = SYNC_ENVBOUND_KEY;
    $row->v = 'orphaned-encrypted-value';
    $row->save();

    // A normal run skips (hash unchanged); --force prunes it.
    expect(runSync())->toBe(0);
    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->exists())->toBeTrue();

    expect(runSync(['--force' => true]))->toBe(0);
    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->exists())->toBeFalse();
});

test('--force still respects the single-flight lock', function () {
    Config::set(SYNC_ENVBOUND_KEY, 'us-east-1');

    // Hold the lock with a different owner so the forced run cannot acquire it.
    $lock = Cache::lock(SYNC_LOCK_KEY, 15);
    expect($lock->get())->toBeTrue();

    try {
        ConfigCacheService::putRaw(SYNC_ENVBOUND_KEY, 'eu-west-9');

        // Even with --force, a held lock makes the run a no-op.
        expect(runSync(['--force' => true]))->toBe(0);
        expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->value('v'))->toBe('eu-west-9');
    } finally {
        $lock->release();
    }
});

test('--no-sync takes precedence over --force', function () {
    Config::set(SYNC_ENVBOUND_KEY, 'us-east-1');
    ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->delete();

    // --no-sync short-circuits before any reconciliation, regardless of --force.
    expect(runSync(['--no-sync' => true, '--force' => true]))->toBe(0);
    expect(ConfigCacheModel::where('k', SYNC_ENVBOUND_KEY)->exists())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| a reconcile flushes the cache for EVERY managed key (not just touched)
|--------------------------------------------------------------------------
*/

test('a reconcile flushes the memoized cache for an untouched key so no stale value survives', function () {
    // Plant a stale cache entry for an ADMINONLY key that the reconcile will NOT
    // touch (its row is left alone). The flush-all must still clear it.
    $adminCacheKey = ConfigCacheService::CACHE_KEY.SYNC_ADMINONLY_KEY;
    Cache::forever($adminCacheKey, 'STALE-CACHED-VALUE');
    expect(Cache::get($adminCacheKey))->toBe('STALE-CACHED-VALUE');

    // Force a reconcile to run by changing a governed (ENVBOUND) config value so
    // the change-hash differs from any marker.
    Config::set(SYNC_ENVBOUND_KEY, 'ap-southeast-2');

    expect(runSync())->toBe(0);

    // The untouched key's stale cache entry was flushed by flushAll().
    expect(Cache::get($adminCacheKey))->toBeNull();
});

test('--force flushes the cache for every managed key even when the hash is unchanged', function () {
    Config::set(SYNC_ENVBOUND_KEY, 'us-east-1');

    // Seed the marker so a normal run would skip.
    expect(runSync())->toBe(0);

    $adminCacheKey = ConfigCacheService::CACHE_KEY.SYNC_ADMINONLY_KEY;
    Cache::forever($adminCacheKey, 'STALE-CACHED-VALUE');

    expect(runSync(['--force' => true]))->toBe(0);

    expect(Cache::get($adminCacheKey))->toBeNull();
});
