<?php

use App\Services\ConfigCacheService;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Config;

/*
|--------------------------------------------------------------------------
| ConfigCacheService env presence + validity helpers
|--------------------------------------------------------------------------
|
|   - envIsSet($envVar): present iff set + non-empty (empty counts as absent)
|   - isLocked($key):    env present AND passes the key's declared rule
|
| Exercised against a real KEYS key with an `in:` rule — captcha.driver
| (env CAPTCHA_DRIVER, rule in:hcaptcha,turnstile,cap) — across four states:
| set+valid, unset, empty string, and set+invalid.
|
| Presence is read from Laravel's Env repository, which is immutable per
| instance, so each state resets the cached repository via reflection after
| mutating the process env, mirroring how a fresh boot resolves the value.
|
| Validates: Requirements 2.1, 2.3, 2.4
|
*/

const ENV_TEST_VAR = 'CAPTCHA_DRIVER';
const ENV_TEST_KEY = 'captcha.driver';

// An ADMINONLY key: no env binding, so it can never be env-locked.
const ENV_TEST_NO_ENV_KEY = 'uikit.custom.css';

function resetEnvRepository(): void
{
    $ref = new ReflectionClass(Env::class);
    $prop = $ref->getProperty('repository');
    $prop->setAccessible(true);
    $prop->setValue(null, null);
}

function setProcessEnv(string $name, ?string $value): void
{
    if ($value === null) {
        putenv($name);
        unset($_ENV[$name], $_SERVER[$name]);
    } else {
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    resetEnvRepository();
}

beforeEach(function () {
    $this->originalEnv = getenv(ENV_TEST_VAR);
    $this->originalConfig = Config::get(ENV_TEST_KEY);
});

afterEach(function () {
    setProcessEnv(ENV_TEST_VAR, $this->originalEnv === false ? null : $this->originalEnv);
    Config::set(ENV_TEST_KEY, $this->originalConfig);
    resetEnvRepository();
});

test('envIsSet returns true when the var is set and non-empty', function () {
    setProcessEnv(ENV_TEST_VAR, 'turnstile');

    expect(ConfigCacheService::envIsSet(ENV_TEST_VAR))->toBeTrue();
});

test('envIsSet returns false when the var is unset', function () {
    setProcessEnv(ENV_TEST_VAR, null);

    expect(ConfigCacheService::envIsSet(ENV_TEST_VAR))->toBeFalse();
});

test('envIsSet treats an empty string as absent (Requirement 2.3)', function () {
    setProcessEnv(ENV_TEST_VAR, '');

    expect(ConfigCacheService::envIsSet(ENV_TEST_VAR))->toBeFalse();
});

test('isLocked is true when the env var is set and valid', function () {
    setProcessEnv(ENV_TEST_VAR, 'turnstile');
    Config::set(ENV_TEST_KEY, 'turnstile');

    expect(ConfigCacheService::isLocked(ENV_TEST_KEY))->toBeTrue();
});

test('isLocked is false when the env var is unset', function () {
    setProcessEnv(ENV_TEST_VAR, null);
    Config::set(ENV_TEST_KEY, 'hcaptcha'); // config default; env absent

    expect(ConfigCacheService::isLocked(ENV_TEST_KEY))->toBeFalse();
});

test('isLocked treats an empty env string as absent (Requirement 2.3)', function () {
    setProcessEnv(ENV_TEST_VAR, '');
    Config::set(ENV_TEST_KEY, 'hcaptcha');

    expect(ConfigCacheService::isLocked(ENV_TEST_KEY))->toBeFalse();
});

test('isLocked is false when the env var is set but invalid (Requirement 2.4)', function () {
    setProcessEnv(ENV_TEST_VAR, 'banana');
    Config::set(ENV_TEST_KEY, 'banana'); // env resolved into config, fails in: rule

    expect(ConfigCacheService::isLocked(ENV_TEST_KEY))->toBeFalse();
});

test('isLocked is false for a key with no env binding (ADMINONLY)', function () {
    expect(ConfigCacheService::envVarFor(ENV_TEST_NO_ENV_KEY))->toBeNull();
    expect(ConfigCacheService::isLocked(ENV_TEST_NO_ENV_KEY))->toBeFalse();
});

test('isLocked flips with env presence for an ENVCONFIG key', function () {
    setProcessEnv(ENV_TEST_VAR, 'cap');
    Config::set(ENV_TEST_KEY, 'cap');
    expect(ConfigCacheService::isLocked(ENV_TEST_KEY))->toBeTrue();

    setProcessEnv(ENV_TEST_VAR, null);
    expect(ConfigCacheService::isLocked(ENV_TEST_KEY))->toBeFalse();
});
