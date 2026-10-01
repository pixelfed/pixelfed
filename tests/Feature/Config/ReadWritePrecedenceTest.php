<?php

use App\Models\ConfigCache as ConfigCacheModel;
use App\Services\ConfigCacheService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

uses(LazilyRefreshDatabase::class);

const RW_UNLISTED_KEY = 'this.key.is.not.cached';

const RW_ENVBOUND_KEY = 'filesystems.disks.s3.region';
const RW_ENVBOUND_VAR = 'AWS_DEFAULT_REGION';

const RW_ENVCONFIG_KEY = 'captcha.driver';
const RW_ENVCONFIG_VAR = 'CAPTCHA_DRIVER';

const RW_ADMINONLY_KEY = 'uikit.custom.css';

const RW_PROTECTED_KEY = 'captcha.hcaptcha.secret';
const RW_PROTECTED_VAR = 'CAPTCHA_H_SECRET';

// Reset Env's cached repository so the next Env::get() re-reads the process env.
function rwResetEnvRepository(): void
{
    $ref = new ReflectionClass(Env::class);
    $prop = $ref->getProperty('repository');
    $prop->setAccessible(true);
    $prop->setValue(null, null);
}

// Set (or clear if null) an env var across every source, then reset the repository.
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

// Forget the memoized cache entry so the next get() re-resolves.
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

test('get() unlisted returns config($key) and persists no row', function () {
    Config::set(RW_UNLISTED_KEY, 'passthrough-value');

    expect(ConfigCacheService::get(RW_UNLISTED_KEY))->toBe('passthrough-value');
    expect(ConfigCacheModel::where('k', RW_UNLISTED_KEY)->exists())->toBeFalse();
});

test('get() env-present returns config value, ignoring a differing DB row', function () {
    // Env present + valid → config() wins over any DB row.
    rwSetProcessEnv(RW_ENVBOUND_VAR, 'us-east-1');
    Config::set(RW_ENVBOUND_KEY, 'us-east-1');
    ConfigCacheService::putRaw(RW_ENVBOUND_KEY, 'eu-west-9');
    rwForget(RW_ENVBOUND_KEY);

    expect(ConfigCacheService::get(RW_ENVBOUND_KEY))->toBe('us-east-1');
});

test('get() env-present returns config value when no DB row exists', function () {
    rwSetProcessEnv(RW_ENVBOUND_VAR, 'us-east-1');
    Config::set(RW_ENVBOUND_KEY, 'us-east-1');

    expect(ConfigCacheService::get(RW_ENVBOUND_KEY))->toBe('us-east-1');
});

test('get() ENVCONFIG env-valid returns config value, ignoring a differing DB row', function () {
    rwSetProcessEnv(RW_ENVCONFIG_VAR, 'turnstile');
    Config::set(RW_ENVCONFIG_KEY, 'turnstile'); // env resolved into config, passes in: rule

    // Plant a DB row with a different value; env authoritative must win.
    ConfigCacheService::putRaw(RW_ENVCONFIG_KEY, 'hcaptcha');
    rwForget(RW_ENVCONFIG_KEY);

    expect(ConfigCacheService::get(RW_ENVCONFIG_KEY))->toBe('turnstile');
});

test('get() ENVCONFIG env-valid returns config value when no DB row exists', function () {
    rwSetProcessEnv(RW_ENVCONFIG_VAR, 'cap');
    Config::set(RW_ENVCONFIG_KEY, 'cap');

    expect(ConfigCacheService::get(RW_ENVCONFIG_KEY))->toBe('cap');
});

test('get() ENVCONFIG env-absent returns the DB row when present', function () {
    rwSetProcessEnv(RW_ENVCONFIG_VAR, null);
    Config::set(RW_ENVCONFIG_KEY, 'hcaptcha'); // config default

    ConfigCacheService::putRaw(RW_ENVCONFIG_KEY, 'turnstile');
    rwForget(RW_ENVCONFIG_KEY);

    expect(ConfigCacheService::get(RW_ENVCONFIG_KEY))->toBe('turnstile');
});

test('get() ENVCONFIG env-absent falls back to config when no DB row', function () {
    rwSetProcessEnv(RW_ENVCONFIG_VAR, null);
    Config::set(RW_ENVCONFIG_KEY, 'hcaptcha');
    rwForget(RW_ENVCONFIG_KEY);

    expect(ConfigCacheService::get(RW_ENVCONFIG_KEY))->toBe('hcaptcha');
});

test('get() ENVCONFIG env-invalid is treated as absent: DB row wins', function () {
    rwSetProcessEnv(RW_ENVCONFIG_VAR, 'banana'); // fails in: rule → treated absent
    Config::set(RW_ENVCONFIG_KEY, 'banana');

    ConfigCacheService::putRaw(RW_ENVCONFIG_KEY, 'turnstile');
    rwForget(RW_ENVCONFIG_KEY);

    expect(ConfigCacheService::get(RW_ENVCONFIG_KEY))->toBe('turnstile');
});

test('get() ENVCONFIG env-invalid with no DB row falls back to config', function () {
    rwSetProcessEnv(RW_ENVCONFIG_VAR, 'banana');
    Config::set(RW_ENVCONFIG_KEY, 'banana');
    rwForget(RW_ENVCONFIG_KEY);

    // No row: readFromDb seeds from config($key) and returns it.
    expect(ConfigCacheService::get(RW_ENVCONFIG_KEY))->toBe('banana');
});

test('get() ADMINONLY returns the DB row when present', function () {
    Config::set(RW_ADMINONLY_KEY, '/* default css */');

    ConfigCacheService::putRaw(RW_ADMINONLY_KEY, '.admin { color: red; }');
    rwForget(RW_ADMINONLY_KEY);

    expect(ConfigCacheService::get(RW_ADMINONLY_KEY))->toBe('.admin { color: red; }');
});

test('get() ADMINONLY falls back to config when no DB row', function () {
    Config::set(RW_ADMINONLY_KEY, '/* default css */');
    rwForget(RW_ADMINONLY_KEY);

    expect(ConfigCacheService::get(RW_ADMINONLY_KEY))->toBe('/* default css */');
});

test('put() unlisted is a no-op: no row written', function () {
    Config::set(RW_UNLISTED_KEY, 'cfg');

    ConfigCacheService::put(RW_UNLISTED_KEY, 'attempted');

    expect(ConfigCacheModel::where('k', RW_UNLISTED_KEY)->exists())->toBeFalse();
});

test('put() env-present is a no-op: no row written', function () {
    // Env-bound key with the env var present is locked → put() is a no-op.
    rwSetProcessEnv(RW_ENVBOUND_VAR, 'us-east-1');
    Config::set(RW_ENVBOUND_KEY, 'us-east-1');

    ConfigCacheService::put(RW_ENVBOUND_KEY, 'eu-west-9');

    expect(ConfigCacheModel::where('k', RW_ENVBOUND_KEY)->exists())->toBeFalse();
    rwForget(RW_ENVBOUND_KEY);
    expect(ConfigCacheService::get(RW_ENVBOUND_KEY))->toBe('us-east-1');
});

test('put() ENVCONFIG env-valid is a no-op: no row written', function () {
    rwSetProcessEnv(RW_ENVCONFIG_VAR, 'turnstile');
    Config::set(RW_ENVCONFIG_KEY, 'turnstile');

    ConfigCacheService::put(RW_ENVCONFIG_KEY, 'hcaptcha');

    expect(ConfigCacheModel::where('k', RW_ENVCONFIG_KEY)->exists())->toBeFalse();
    rwForget(RW_ENVCONFIG_KEY);
    expect(ConfigCacheService::get(RW_ENVCONFIG_KEY))->toBe('turnstile');
});

test('put() ENVCONFIG env-absent writes the row', function () {
    rwSetProcessEnv(RW_ENVCONFIG_VAR, null);
    Config::set(RW_ENVCONFIG_KEY, 'hcaptcha');

    ConfigCacheService::put(RW_ENVCONFIG_KEY, 'turnstile');

    $row = ConfigCacheModel::where('k', RW_ENVCONFIG_KEY)->first();
    expect($row)->not->toBeNull();
    expect($row->v)->toBe('turnstile');

    rwForget(RW_ENVCONFIG_KEY);
    expect(ConfigCacheService::get(RW_ENVCONFIG_KEY))->toBe('turnstile');
});

test('put() ADMINONLY writes the row', function () {
    Config::set(RW_ADMINONLY_KEY, '/* default css */');

    ConfigCacheService::put(RW_ADMINONLY_KEY, '.x { color: blue; }');

    $row = ConfigCacheModel::where('k', RW_ADMINONLY_KEY)->first();
    expect($row)->not->toBeNull();
    expect($row->v)->toBe('.x { color: blue; }');

    rwForget(RW_ADMINONLY_KEY);
    expect(ConfigCacheService::get(RW_ADMINONLY_KEY))->toBe('.x { color: blue; }');
});

test('put() protected key stores value encrypted at rest; get() decrypts back', function () {
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

test('get() protected key with empty config value seeds no row (no encrypt of "")', function () {
    // An unset secret resolves to '' in config; it must not be encrypted and written.
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
