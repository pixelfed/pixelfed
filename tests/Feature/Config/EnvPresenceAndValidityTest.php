<?php

use App\Services\ConfigCacheService;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Config;

/*
|--------------------------------------------------------------------------
| ConfigCacheService env presence + validity helpers (Task 5)
|--------------------------------------------------------------------------
|
| Covers the two Laravel-native helpers that drive lock / precedence
| decisions:
|
|   - envIsSet($envVar):            present iff set + non-empty (empty = absent)
|   - envIsPresentAndValid($env):   present AND passes the key's declared rule
|   - envIsPresentAndValidForKey($key): key-oriented companion used by isLocked
|
| We exercise a real KEYS key with an `in:` rule — captcha.driver
| (env CAPTCHA_DRIVER, rule in:hcaptcha,turnstile,cap) — across the four
| states: set+valid, unset, empty string, and set+invalid.
|
| Presence is read from Laravel's Env repository (Illuminate\Support\Env),
| which is immutable per instance, so each state resets the cached repository
| via reflection after mutating the process env, mirroring how a fresh boot
| would resolve the value.
|
| Validates: Requirements 2.1, 2.3, 2.4
|
*/

const ENV_TEST_VAR = 'CAPTCHA_DRIVER';
const ENV_TEST_KEY = 'captcha.driver';

/**
 * Reset Illuminate\Support\Env's cached (immutable) repository so the next
 * Env::get() re-reads the process environment.
 */
function resetEnvRepository(): void
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

test('envIsPresentAndValid is true when set and valid', function () {
    setProcessEnv(ENV_TEST_VAR, 'turnstile');
    Config::set(ENV_TEST_KEY, 'turnstile');

    expect(ConfigCacheService::envIsPresentAndValid(ENV_TEST_VAR))->toBeTrue();
    expect(ConfigCacheService::envIsPresentAndValidForKey(ENV_TEST_KEY))->toBeTrue();
});

test('envIsPresentAndValid is false when unset', function () {
    setProcessEnv(ENV_TEST_VAR, null);
    Config::set(ENV_TEST_KEY, 'hcaptcha'); // config default; env absent

    expect(ConfigCacheService::envIsPresentAndValid(ENV_TEST_VAR))->toBeFalse();
    expect(ConfigCacheService::envIsPresentAndValidForKey(ENV_TEST_KEY))->toBeFalse();
});

test('envIsPresentAndValid treats empty string as absent (Requirement 2.3)', function () {
    setProcessEnv(ENV_TEST_VAR, '');
    Config::set(ENV_TEST_KEY, 'hcaptcha');

    expect(ConfigCacheService::envIsPresentAndValid(ENV_TEST_VAR))->toBeFalse();
    expect(ConfigCacheService::envIsPresentAndValidForKey(ENV_TEST_KEY))->toBeFalse();
});

test('envIsPresentAndValid is false when set but invalid (Requirement 2.4)', function () {
    setProcessEnv(ENV_TEST_VAR, 'banana');
    Config::set(ENV_TEST_KEY, 'banana'); // env resolved into config, fails in: rule

    expect(ConfigCacheService::envIsPresentAndValid(ENV_TEST_VAR))->toBeFalse();
    expect(ConfigCacheService::envIsPresentAndValidForKey(ENV_TEST_KEY))->toBeFalse();
});

test('envIsPresentAndValid returns false for a null env var name', function () {
    expect(ConfigCacheService::envIsPresentAndValid(null))->toBeFalse();
});

test('envIsPresentAndValidForKey drives isLocked for ENVCONFIG keys', function () {
    // Present + valid → locked.
    setProcessEnv(ENV_TEST_VAR, 'cap');
    Config::set(ENV_TEST_KEY, 'cap');
    expect(ConfigCacheService::isLocked(ENV_TEST_KEY))->toBeTrue();

    // Absent → unlocked (editable).
    setProcessEnv(ENV_TEST_VAR, null);
    expect(ConfigCacheService::isLocked(ENV_TEST_KEY))->toBeFalse();
});
