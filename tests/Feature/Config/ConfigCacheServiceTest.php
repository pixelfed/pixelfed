<?php

use App\Http\Controllers\Api\v2026\Admin\ConfigCacheController;
use App\Models\ConfigCache as ConfigCacheModel;
use App\Services\ConfigCacheService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| ConfigCacheService — service-level unit/regression coverage
|--------------------------------------------------------------------------
|
| Complements the command/API tests by exercising the service methods that
| moved out of the sync command (sync/refreshEnv/configHash/syncEnabled) and
| the PROTECTED_KEYS registry directly, plus JSON casting and the cache-key
| constants. Env presence is driven the same way as the sibling suites.
|
*/

const SVC_ENVBOUND_KEY = 'filesystems.disks.s3.region';
const SVC_ENVBOUND_VAR = 'AWS_DEFAULT_REGION';

const SVC_ADMINONLY_JSON_KEY = 'config.discover.features';

const SVC_MARKER_KEY = 'config-cache:sync-hash';
const SVC_LOCK_KEY = 'config-cache:sync';

function svcResetEnvRepository(): void
{
    $ref = new ReflectionClass(Env::class);
    $prop = $ref->getProperty('repository');
    $prop->setAccessible(true);
    $prop->setValue(null, null);
}

function svcSetProcessEnv(string $name, ?string $value): void
{
    if ($value === null) {
        putenv($name);
        unset($_ENV[$name], $_SERVER[$name]);
    } else {
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    svcResetEnvRepository();
}

function svcForget(string $key): void
{
    Cache::forget(ConfigCacheService::CACHE_KEY.$key);
}

beforeEach(function () {
    $this->svcOriginalEnv = [
        SVC_ENVBOUND_VAR => getenv(SVC_ENVBOUND_VAR),
        'PIXELFED_CONFIG_CACHE_SYNC' => getenv('PIXELFED_CONFIG_CACHE_SYNC'),
    ];

    Cache::forget(SVC_MARKER_KEY);
    Cache::forget(SVC_LOCK_KEY);
    svcForget(SVC_ENVBOUND_KEY);
    svcForget(SVC_ADMINONLY_JSON_KEY);

    svcSetProcessEnv(SVC_ENVBOUND_VAR, null);
    svcSetProcessEnv('PIXELFED_CONFIG_CACHE_SYNC', null);
});

afterEach(function () {
    foreach ($this->svcOriginalEnv as $var => $value) {
        svcSetProcessEnv($var, $value === false ? null : $value);
    }
    svcResetEnvRepository();

    Cache::forget(SVC_MARKER_KEY);
    Cache::forget(SVC_LOCK_KEY);
    svcForget(SVC_ENVBOUND_KEY);
    svcForget(SVC_ADMINONLY_JSON_KEY);
});

/*
|--------------------------------------------------------------------------
| PROTECTED_KEYS / isProtected()
|--------------------------------------------------------------------------
*/

test('PROTECTED_KEYS is exactly the set of secret keys', function () {
    expect(ConfigCacheService::PROTECTED_KEYS)->toBe([
        'filesystems.disks.s3.secret',
        'filesystems.disks.spaces.secret',
        'captcha.hcaptcha.secret',
        'captcha.turnstile.secret',
        'captcha.cap.secret',
    ]);
});

test('isProtected returns true for every PROTECTED_KEYS entry', function () {
    foreach (ConfigCacheService::PROTECTED_KEYS as $key) {
        expect(ConfigCacheService::isProtected($key))->toBeTrue("[$key] should be protected");
    }
});

test('isProtected returns false for non-secret keys, including the S3/Spaces access keys', function () {
    // Regression: the access-key IDs are intentionally NOT protected (only the
    // secret keys are). This pins that decision against accidental drift.
    foreach ([
        'filesystems.disks.s3.key',
        'filesystems.disks.spaces.key',
        'filesystems.disks.s3.region',
        'uikit.custom.css',
        'this.key.is.not.cached',
    ] as $key) {
        expect(ConfigCacheService::isProtected($key))->toBeFalse("[$key] should NOT be protected");
    }
});

test('every PROTECTED key is a registered cached key', function () {
    foreach (ConfigCacheService::PROTECTED_KEYS as $key) {
        expect(ConfigCacheService::isCached($key))->toBeTrue("[$key] must exist in KEYS");
    }
});

/*
|--------------------------------------------------------------------------
| configHash() properties
|--------------------------------------------------------------------------
*/

test('configHash is stable for the same governed values', function () {
    expect(ConfigCacheService::configHash())->toBe(ConfigCacheService::configHash());
});

test('configHash changes when a governed ENVCONFIG value changes', function () {
    Config::set(SVC_ENVBOUND_KEY, 'us-east-1');
    $before = ConfigCacheService::configHash();

    Config::set(SVC_ENVBOUND_KEY, 'eu-west-9');
    $after = ConfigCacheService::configHash();

    expect($after)->not->toBe($before);
});

test('configHash is unaffected by ADMINONLY (non-governed) values', function () {
    $before = ConfigCacheService::configHash();

    // ADMINONLY keys are not part of the governed env hash.
    Config::set('uikit.custom.css', '.changed { color: red; }');
    $after = ConfigCacheService::configHash();

    expect($after)->toBe($before);
});

/*
|--------------------------------------------------------------------------
| syncEnabled()
|--------------------------------------------------------------------------
*/

test('syncEnabled defaults to true when PIXELFED_CONFIG_CACHE_SYNC is unset', function () {
    svcSetProcessEnv('PIXELFED_CONFIG_CACHE_SYNC', null);

    expect(ConfigCacheService::syncEnabled())->toBeTrue();
});

test('syncEnabled is false when PIXELFED_CONFIG_CACHE_SYNC=false', function () {
    svcSetProcessEnv('PIXELFED_CONFIG_CACHE_SYNC', 'false');

    expect(ConfigCacheService::syncEnabled())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| sync() — direct service invocation (not via the artisan command)
|--------------------------------------------------------------------------
*/

test('sync() seeds a governed key and writes the change-hash marker', function () {
    Config::set(SVC_ENVBOUND_KEY, 'us-east-1');
    ConfigCacheModel::where('k', SVC_ENVBOUND_KEY)->delete();

    ConfigCacheService::sync();

    expect(ConfigCacheModel::where('k', SVC_ENVBOUND_KEY)->exists())->toBeTrue();
    expect(Cache::get(SVC_MARKER_KEY))->toBe(ConfigCacheService::configHash());
});

test('sync() is hash-gated: an unchanged second run does not reconcile a stale row', function () {
    Config::set(SVC_ENVBOUND_KEY, 'us-east-1');
    ConfigCacheService::sync();

    // Corrupt the row without changing any governed config value.
    ConfigCacheService::putRaw(SVC_ENVBOUND_KEY, 'eu-west-9');

    // Env absent → the row is DB-authoritative and preserved; but the point here
    // is the gate: a non-forced run with an unchanged hash is a no-op.
    ConfigCacheService::sync();

    expect(ConfigCacheModel::where('k', SVC_ENVBOUND_KEY)->value('v'))->toBe('eu-west-9');
});

test('sync(true) forces a reconcile that overwrites a drifted env-locked row', function () {
    // Env present + valid → env-authoritative: a drifted row is overwritten.
    svcSetProcessEnv(SVC_ENVBOUND_VAR, 'us-east-1');
    Config::set(SVC_ENVBOUND_KEY, 'us-east-1');

    ConfigCacheService::sync();

    ConfigCacheService::putRaw(SVC_ENVBOUND_KEY, 'eu-west-9');

    ConfigCacheService::sync(true);

    expect(ConfigCacheModel::where('k', SVC_ENVBOUND_KEY)->value('v'))->toBe('us-east-1');
});

test('sync() is a no-op while an external lock is held', function () {
    Config::set(SVC_ENVBOUND_KEY, 'us-east-1');
    ConfigCacheService::putRaw(SVC_ENVBOUND_KEY, 'eu-west-9');

    $lock = Cache::lock(SVC_LOCK_KEY, 15);
    expect($lock->get())->toBeTrue();

    try {
        ConfigCacheService::sync(true);
        // Lock held → no reconcile, stale row unchanged, no marker written.
        expect(ConfigCacheModel::where('k', SVC_ENVBOUND_KEY)->value('v'))->toBe('eu-west-9');
        expect(Cache::get(SVC_MARKER_KEY))->toBeNull();
    } finally {
        $lock->release();
    }
});

/*
|--------------------------------------------------------------------------
| castFromDb — JSON keys
|--------------------------------------------------------------------------
*/

test('get() JSON key decodes the stored text into an array', function () {
    // config.discover.features is ADMINONLY + rule json.
    ConfigCacheModel::where('k', SVC_ADMINONLY_JSON_KEY)->delete();
    $row = new ConfigCacheModel;
    $row->k = SVC_ADMINONLY_JSON_KEY;
    $row->v = json_encode(['a' => 1, 'b' => true]);
    $row->save();
    svcForget(SVC_ADMINONLY_JSON_KEY);

    $val = ConfigCacheService::get(SVC_ADMINONLY_JSON_KEY);

    expect($val)->toBeArray();
    expect($val)->toBe(['a' => 1, 'b' => true]);
});

test('get() JSON key falls back to the raw string when it is not valid JSON', function () {
    ConfigCacheModel::where('k', SVC_ADMINONLY_JSON_KEY)->delete();
    $row = new ConfigCacheModel;
    $row->k = SVC_ADMINONLY_JSON_KEY;
    $row->v = 'not-json';
    $row->save();
    svcForget(SVC_ADMINONLY_JSON_KEY);

    expect(ConfigCacheService::get(SVC_ADMINONLY_JSON_KEY))->toBe('not-json');
});

/*
|--------------------------------------------------------------------------
| cache-key constants (regression: literals used across the suites must match)
|--------------------------------------------------------------------------
*/

test('MARKER_KEY and LOCK_KEY hold their expected values', function () {
    expect(ConfigCacheService::MARKER_KEY)->toBe('config-cache:sync-hash');
    expect(ConfigCacheService::LOCK_KEY)->toBe('config-cache:sync');
    expect(ConfigCacheService::LOCK_TTL)->toBe(15);
});

/*
|--------------------------------------------------------------------------
| maskProtectedConfig() — boundary inputs
|--------------------------------------------------------------------------
*/

test('maskProtectedConfig returns null for null and empty string for empty', function () {
    expect(ConfigCacheController::maskProtectedConfig(null))->toBeNull();
    expect(ConfigCacheController::maskProtectedConfig(''))->toBe('');
});

test('maskProtectedConfig fully masks a value shorter than 8 chars', function () {
    expect(ConfigCacheController::maskProtectedConfig('abc'))->toBe('***');
    expect(ConfigCacheController::maskProtectedConfig('abcdefg'))->toBe('*******');
});

test('maskProtectedConfig shows 4 chars each end for values of 9+ chars', function () {
    // Str::mask leaves first 4 and last 4 visible, masking the middle.
    expect(ConfigCacheController::maskProtectedConfig('abcdefghij'))->toBe('abcd**ghij');
    $masked = ConfigCacheController::maskProtectedConfig('original-secret-value');
    expect($masked)->toStartWith('orig');
    expect($masked)->toEndWith('alue');
    expect($masked)->toContain('*');
    // The raw secret must never leak through the mask.
    expect($masked)->not->toContain('secret');
});
