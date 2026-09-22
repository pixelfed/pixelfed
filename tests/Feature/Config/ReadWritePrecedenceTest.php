<?php

use App\Models\ConfigCache as ConfigCacheModel;
use App\Services\ConfigCacheService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| ConfigCacheService read/write precedence (Task 8)
|--------------------------------------------------------------------------
|
| Exercises get()/put() across the three-list ownership model. There is no
| enable_cc dimension — the master switch has been removed, so precedence is
| computed purely from the list + live env presence/validity.
|
| Two lists only: ENVCONFIG (env-bound) and ADMINONLY (no env var). ENVBOUND
| was removed — every env-bound key now follows the ENVCONFIG rule.
|
| get(): (env valid / absent / invalid) × (DB row present/absent)
|   - unlisted      → config($key), no row persisted
|   - env present+valid → config($key) regardless of any DB row
|   - env absent    → DB row if present else config fallback
|   - env invalid   → treated absent (DB row else config fallback)
|   - ADMINONLY (no env) → DB row if present else config fallback
|
| put(): no-op vs write
|   - unlisted / env-present (locked) → no-op (no row written)
|   - env-absent / ADMINONLY          → write
|   - protected key write → encrypted at rest; get() decrypts back
|
| Real KEYS keys are reused so the test tracks the actual registry:
|   ENV-BOUND string : filesystems.disks.s3.region  (AWS_DEFAULT_REGION)
|   ENV-BOUND enum   : captcha.driver               (CAPTCHA_DRIVER, in:hcaptcha,turnstile,cap)
|   ADMINONLY        : uikit.custom.css             (no env var)
|   PROTECTED        : captcha.hcaptcha.secret      (CAPTCHA_H_SECRET, env-bound + secret)
|
| Env presence is driven the same way as EnvPresenceAndValidityTest: mutate
| the process env across every source the Env repository reads, then reset the
| cached (immutable) repository via reflection so the next Env::get() re-reads.
|
| Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 7.1, 7.2, 7.3, 7.4
|
*/

const RW_UNLISTED_KEY = 'this.key.is.not.cached';

const RW_ENVBOUND_KEY = 'filesystems.disks.s3.region';
const RW_ENVBOUND_VAR = 'AWS_DEFAULT_REGION';

const RW_ENVCONFIG_KEY = 'captcha.driver';
const RW_ENVCONFIG_VAR = 'CAPTCHA_DRIVER';

const RW_ADMINONLY_KEY = 'uikit.custom.css';

const RW_PROTECTED_KEY = 'captcha.hcaptcha.secret';
const RW_PROTECTED_VAR = 'CAPTCHA_H_SECRET';

/**
 * Reset Illuminate\Support\Env's cached (immutable) repository so the next
 * Env::get() re-reads the process environment.
 */
function rwResetEnvRepository(): void
{
    $ref = new ReflectionClass(Env::class);
    $prop = $ref->getProperty('repository');
    $prop->setAccessible(true);
    $prop->setValue(null, null);
}

/**
 * Set (or clear when $value === null) an env var across every source the Env
 * repository reads, then reset the cached repository.
 */
function rwSetProcessEnv(string $name, ?string $value): void
{
    if ($value === null) {
        putenv($name);
        unset($_ENV[$name], $_SERVER[$name]);
    } else {
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    rwResetEnvRepository();
}

/**
 * Forget the memoized 12h cache entry for a key so the next get() re-resolves
 * from the DB row / config fallback.
 */
function rwForget(string $key): void
{
    Cache::forget(ConfigCacheService::CACHE_KEY.$key);
}

beforeEach(function () {
    $this->originalEnv = [
        RW_ENVBOUND_VAR => getenv(RW_ENVBOUND_VAR),
        RW_ENVCONFIG_VAR => getenv(RW_ENVCONFIG_VAR),
        RW_PROTECTED_VAR => getenv(RW_PROTECTED_VAR),
    ];
    $this->originalConfig = [
        RW_ENVBOUND_KEY => Config::get(RW_ENVBOUND_KEY),
        RW_ENVCONFIG_KEY => Config::get(RW_ENVCONFIG_KEY),
        RW_ADMINONLY_KEY => Config::get(RW_ADMINONLY_KEY),
        RW_PROTECTED_KEY => Config::get(RW_PROTECTED_KEY),
    ];

    // Start each case from a known env-absent baseline.
    foreach (array_keys($this->originalEnv) as $var) {
        rwSetProcessEnv($var, null);
    }
});

afterEach(function () {
    foreach ($this->originalEnv as $var => $value) {
        rwSetProcessEnv($var, $value === false ? null : $value);
    }
    foreach ($this->originalConfig as $key => $value) {
        Config::set($key, $value);
    }
    rwResetEnvRepository();

    foreach ([RW_ENVBOUND_KEY, RW_ENVCONFIG_KEY, RW_ADMINONLY_KEY, RW_PROTECTED_KEY, RW_UNLISTED_KEY] as $key) {
        rwForget($key);
    }
});

/*
|--------------------------------------------------------------------------
| get() — unlisted
|--------------------------------------------------------------------------
*/

test('get() unlisted returns config($key) and persists no row (4.1)', function () {
    Config::set(RW_UNLISTED_KEY, 'passthrough-value');

    expect(ConfigCacheService::get(RW_UNLISTED_KEY))->toBe('passthrough-value');
    expect(ConfigCacheModel::where('k', RW_UNLISTED_KEY)->exists())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| get() — env-bound string key, env PRESENT (env authoritative over DB)
|--------------------------------------------------------------------------
*/

test('get() env-present returns config value, ignoring a differing DB row (4.2)', function () {
    // s3.region is env-bound (ENVCONFIG). With the env var present + valid,
    // config() wins over any DB row.
    rwSetProcessEnv(RW_ENVBOUND_VAR, 'us-east-1');
    Config::set(RW_ENVBOUND_KEY, 'us-east-1');
    ConfigCacheService::putRaw(RW_ENVBOUND_KEY, 'eu-west-9');
    rwForget(RW_ENVBOUND_KEY);

    expect(ConfigCacheService::get(RW_ENVBOUND_KEY))->toBe('us-east-1');
});

test('get() env-present returns config value when no DB row exists (4.2)', function () {
    rwSetProcessEnv(RW_ENVBOUND_VAR, 'us-east-1');
    Config::set(RW_ENVBOUND_KEY, 'us-east-1');

    expect(ConfigCacheService::get(RW_ENVBOUND_KEY))->toBe('us-east-1');
});

/*
|--------------------------------------------------------------------------
| get() — ENVCONFIG + env present + valid (env authoritative)
|--------------------------------------------------------------------------
*/

test('get() ENVCONFIG env-valid returns config value, ignoring a differing DB row (4.3)', function () {
    rwSetProcessEnv(RW_ENVCONFIG_VAR, 'turnstile');
    Config::set(RW_ENVCONFIG_KEY, 'turnstile'); // env resolved into config, passes in: rule

    // Plant a DB row with a different value; env authoritative must win.
    ConfigCacheService::putRaw(RW_ENVCONFIG_KEY, 'hcaptcha');
    rwForget(RW_ENVCONFIG_KEY);

    expect(ConfigCacheService::get(RW_ENVCONFIG_KEY))->toBe('turnstile');
});

test('get() ENVCONFIG env-valid returns config value when no DB row exists (4.3)', function () {
    rwSetProcessEnv(RW_ENVCONFIG_VAR, 'cap');
    Config::set(RW_ENVCONFIG_KEY, 'cap');

    expect(ConfigCacheService::get(RW_ENVCONFIG_KEY))->toBe('cap');
});

/*
|--------------------------------------------------------------------------
| get() — ENVCONFIG + env absent (DB row wins, else config fallback)
|--------------------------------------------------------------------------
*/

test('get() ENVCONFIG env-absent returns the DB row when present (4.4)', function () {
    rwSetProcessEnv(RW_ENVCONFIG_VAR, null);
    Config::set(RW_ENVCONFIG_KEY, 'hcaptcha'); // config default

    ConfigCacheService::putRaw(RW_ENVCONFIG_KEY, 'turnstile');
    rwForget(RW_ENVCONFIG_KEY);

    expect(ConfigCacheService::get(RW_ENVCONFIG_KEY))->toBe('turnstile');
});

test('get() ENVCONFIG env-absent falls back to config when no DB row (4.4)', function () {
    rwSetProcessEnv(RW_ENVCONFIG_VAR, null);
    Config::set(RW_ENVCONFIG_KEY, 'hcaptcha');
    rwForget(RW_ENVCONFIG_KEY);

    expect(ConfigCacheService::get(RW_ENVCONFIG_KEY))->toBe('hcaptcha');
});

/*
|--------------------------------------------------------------------------
| get() — ENVCONFIG + env invalid (treated as absent)
|--------------------------------------------------------------------------
*/

test('get() ENVCONFIG env-invalid is treated as absent: DB row wins (4.4)', function () {
    rwSetProcessEnv(RW_ENVCONFIG_VAR, 'banana'); // fails in: rule → treated absent
    Config::set(RW_ENVCONFIG_KEY, 'banana');

    ConfigCacheService::putRaw(RW_ENVCONFIG_KEY, 'turnstile');
    rwForget(RW_ENVCONFIG_KEY);

    expect(ConfigCacheService::get(RW_ENVCONFIG_KEY))->toBe('turnstile');
});

test('get() ENVCONFIG env-invalid with no DB row falls back to config (4.4)', function () {
    rwSetProcessEnv(RW_ENVCONFIG_VAR, 'banana');
    Config::set(RW_ENVCONFIG_KEY, 'banana');
    rwForget(RW_ENVCONFIG_KEY);

    // No row: readFromDb seeds from config($key) and returns it.
    expect(ConfigCacheService::get(RW_ENVCONFIG_KEY))->toBe('banana');
});

/*
|--------------------------------------------------------------------------
| get() — ADMINONLY (DB row wins, else config fallback)
|--------------------------------------------------------------------------
*/

test('get() ADMINONLY returns the DB row when present (4.5)', function () {
    Config::set(RW_ADMINONLY_KEY, '/* default css */');

    ConfigCacheService::putRaw(RW_ADMINONLY_KEY, '.admin { color: red; }');
    rwForget(RW_ADMINONLY_KEY);

    expect(ConfigCacheService::get(RW_ADMINONLY_KEY))->toBe('.admin { color: red; }');
});

test('get() ADMINONLY falls back to config when no DB row (4.5)', function () {
    Config::set(RW_ADMINONLY_KEY, '/* default css */');
    rwForget(RW_ADMINONLY_KEY);

    expect(ConfigCacheService::get(RW_ADMINONLY_KEY))->toBe('/* default css */');
});

/*
|--------------------------------------------------------------------------
| put() — no-op branches (unlisted / env-present locked)
|--------------------------------------------------------------------------
*/

test('put() unlisted is a no-op: no row written (7.1)', function () {
    Config::set(RW_UNLISTED_KEY, 'cfg');

    ConfigCacheService::put(RW_UNLISTED_KEY, 'attempted');

    expect(ConfigCacheModel::where('k', RW_UNLISTED_KEY)->exists())->toBeFalse();
});

test('put() env-present is a no-op: no row written (7.2)', function () {
    // Env-bound key with the env var present is locked → put() is a no-op.
    rwSetProcessEnv(RW_ENVBOUND_VAR, 'us-east-1');
    Config::set(RW_ENVBOUND_KEY, 'us-east-1');

    ConfigCacheService::put(RW_ENVBOUND_KEY, 'eu-west-9');

    expect(ConfigCacheModel::where('k', RW_ENVBOUND_KEY)->exists())->toBeFalse();
    rwForget(RW_ENVBOUND_KEY);
    expect(ConfigCacheService::get(RW_ENVBOUND_KEY))->toBe('us-east-1');
});

test('put() ENVCONFIG env-valid is a no-op: no row written (7.3)', function () {
    rwSetProcessEnv(RW_ENVCONFIG_VAR, 'turnstile');
    Config::set(RW_ENVCONFIG_KEY, 'turnstile');

    ConfigCacheService::put(RW_ENVCONFIG_KEY, 'hcaptcha');

    expect(ConfigCacheModel::where('k', RW_ENVCONFIG_KEY)->exists())->toBeFalse();
    rwForget(RW_ENVCONFIG_KEY);
    expect(ConfigCacheService::get(RW_ENVCONFIG_KEY))->toBe('turnstile');
});

/*
|--------------------------------------------------------------------------
| put() — write branches (ENVCONFIG-env-absent / ADMINONLY)
|--------------------------------------------------------------------------
*/

test('put() ENVCONFIG env-absent writes the row (7.4)', function () {
    rwSetProcessEnv(RW_ENVCONFIG_VAR, null);
    Config::set(RW_ENVCONFIG_KEY, 'hcaptcha');

    ConfigCacheService::put(RW_ENVCONFIG_KEY, 'turnstile');

    $row = ConfigCacheModel::where('k', RW_ENVCONFIG_KEY)->first();
    expect($row)->not->toBeNull();
    expect($row->v)->toBe('turnstile');

    rwForget(RW_ENVCONFIG_KEY);
    expect(ConfigCacheService::get(RW_ENVCONFIG_KEY))->toBe('turnstile');
});

test('put() ADMINONLY writes the row (7.4)', function () {
    Config::set(RW_ADMINONLY_KEY, '/* default css */');

    ConfigCacheService::put(RW_ADMINONLY_KEY, '.x { color: blue; }');

    $row = ConfigCacheModel::where('k', RW_ADMINONLY_KEY)->first();
    expect($row)->not->toBeNull();
    expect($row->v)->toBe('.x { color: blue; }');

    rwForget(RW_ADMINONLY_KEY);
    expect(ConfigCacheService::get(RW_ADMINONLY_KEY))->toBe('.x { color: blue; }');
});

/*
|--------------------------------------------------------------------------
| put() — protected key encryption at rest (4.6)
|--------------------------------------------------------------------------
*/

test('put() protected key stores value encrypted at rest; get() decrypts back (4.6)', function () {
    // Ensure the env is absent so the write path runs (ENVCONFIG + secret).
    rwSetProcessEnv(RW_PROTECTED_VAR, null);
    Config::set(RW_PROTECTED_KEY, null);

    $plaintext = 'super-secret-hcaptcha-value';
    ConfigCacheService::put(RW_PROTECTED_KEY, $plaintext);

    $row = ConfigCacheModel::where('k', RW_PROTECTED_KEY)->first();
    expect($row)->not->toBeNull();

    // Stored value must NOT be plaintext...
    expect($row->v)->not->toBe($plaintext);
    // ...and must decrypt back to the original plaintext.
    expect(decrypt($row->v))->toBe($plaintext);

    // get() returns the decrypted value.
    rwForget(RW_PROTECTED_KEY);
    expect(ConfigCacheService::get(RW_PROTECTED_KEY))->toBe($plaintext);
});

/*
|--------------------------------------------------------------------------
| get() — empty/null config value must NOT seed a DB row
|--------------------------------------------------------------------------
*/

test('get() protected key with empty config value seeds no row (no encrypt of "")', function () {
    // ENVCONFIG + secret with env absent → read path runs. An unset secret
    // resolves to '' in config; that must not be encrypted and written.
    rwSetProcessEnv(RW_PROTECTED_VAR, null);
    Config::set(RW_PROTECTED_KEY, '');
    ConfigCacheModel::where('k', RW_PROTECTED_KEY)->delete();
    rwForget(RW_PROTECTED_KEY);

    expect(ConfigCacheService::get(RW_PROTECTED_KEY))->toBe('');
    expect(ConfigCacheModel::where('k', RW_PROTECTED_KEY)->exists())->toBeFalse();
});

test('get() ADMINONLY with empty config value seeds no row', function () {
    Config::set(RW_ADMINONLY_KEY, '');
    ConfigCacheModel::where('k', RW_ADMINONLY_KEY)->delete();
    rwForget(RW_ADMINONLY_KEY);

    expect(ConfigCacheService::get(RW_ADMINONLY_KEY))->toBe('');
    expect(ConfigCacheModel::where('k', RW_ADMINONLY_KEY)->exists())->toBeFalse();
});

test('get() ADMINONLY with null config value seeds no row', function () {
    Config::set(RW_ADMINONLY_KEY, null);
    ConfigCacheModel::where('k', RW_ADMINONLY_KEY)->delete();
    rwForget(RW_ADMINONLY_KEY);

    expect(ConfigCacheService::get(RW_ADMINONLY_KEY))->toBeNull();
    expect(ConfigCacheModel::where('k', RW_ADMINONLY_KEY)->exists())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| get() — DB value is cast back to its declared type
|--------------------------------------------------------------------------
*/

test('get() boolean key returns a real bool, not the string "0", from a DB row', function () {
    // autospam.nlp.enabled is ADMINONLY + rule boolean; config default is false.
    $key = 'autospam.nlp.enabled';

    ConfigCacheModel::where('k', $key)->delete();
    $row = new ConfigCacheModel;
    $row->k = $key;
    $row->v = '0'; // how a stored false reads back from the text column
    $row->save();
    rwForget($key);

    $val = ConfigCacheService::get($key);
    expect($val)->toBeBool();
    expect($val)->toBeFalse();
});

test('get() boolean key returns true for a stored "1"', function () {
    $key = 'autospam.nlp.enabled';

    ConfigCacheModel::where('k', $key)->delete();
    $row = new ConfigCacheModel;
    $row->k = $key;
    $row->v = '1';
    $row->save();
    rwForget($key);

    $val = ConfigCacheService::get($key);
    expect($val)->toBeBool();
    expect($val)->toBeTrue();
});

test('get() integer key returns a real int from a DB row', function () {
    // instance.stats.total_local_posts is ADMINONLY + rule integer.
    $key = 'instance.stats.total_local_posts';

    ConfigCacheModel::where('k', $key)->delete();
    $row = new ConfigCacheModel;
    $row->k = $key;
    $row->v = '42';
    $row->save();
    rwForget($key);

    $val = ConfigCacheService::get($key);
    expect($val)->toBeInt();
    expect($val)->toBe(42);

    ConfigCacheModel::where('k', $key)->delete();
    rwForget($key);
});
