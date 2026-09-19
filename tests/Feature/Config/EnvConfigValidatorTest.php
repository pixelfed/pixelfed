<?php

use App\Services\Config\EnvConfigValidator;
use App\Services\Config\InvalidEnvironmentConfigException;
use App\Services\ConfigCacheService;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| EnvConfigValidator — boot-time .env validation (Task 11)
|--------------------------------------------------------------------------
|
| Covers the fail-fast boot validator that governs every present ENVCONFIG
| env var against the rule declared in ConfigCacheService::KEYS:
|
|   - governed env present + valid   → validateBootEnv() does NOT throw
|   - governed env present + invalid → throws InvalidEnvironmentConfigException
|                                       naming the var + offending value
|   - governed env absent / empty    → no throw, no warn (absence is legal)
|   - no rule declared for a key      → isValidEnvValue() returns true (warn
|                                       branch is non-fatal)
|
| Env presence is driven the same way as the rest of the suite: mutate the
| process env across every source the Env repository reads, then reset the
| cached (immutable) repository via reflection so the next Env::get() re-reads.
|
| Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5
|
*/

// A real ENVCONFIG key with an in: rule — the classic IMAGE_DRIVER=banana
// case is modelled here with captcha.driver (in:hcaptcha,turnstile,cap).
const EV_KEY = 'captcha.driver';
const EV_VAR = 'CAPTCHA_DRIVER';

/**
 * Reset Illuminate\Support\Env's cached (immutable) repository so the next
 * Env::get() re-reads the process environment.
 */
function evResetEnvRepository(): void
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
function evSetProcessEnv(string $name, ?string $value): void
{
    if ($value === null) {
        putenv($name);
        unset($_ENV[$name], $_SERVER[$name]);
    } else {
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    evResetEnvRepository();
}

beforeEach(function () {
    $this->originalEnv = getenv(EV_VAR);
    $this->originalConfig = Config::get(EV_KEY);

    // Start every governed env var absent so a pre-existing .env value (e.g.
    // IMAGE_DRIVER, MAX_PHOTO_SIZE) can't make validateBootEnv() throw for an
    // unrelated reason. We only re-populate the vars a given case cares about.

    foreach (ConfigCacheService::keysInList('ENVCONFIG') as $k) {
        evSetProcessEnv(ConfigCacheService::envVarFor($k), null);
    }
});

afterEach(function () {
    evSetProcessEnv(EV_VAR, $this->originalEnv === false ? null : $this->originalEnv);
    Config::set(EV_KEY, $this->originalConfig);
    evResetEnvRepository();
});

test('validateBootEnv does not throw for a present, valid governed env var (3.1)', function () {
    evSetProcessEnv(EV_VAR, 'turnstile');
    Config::set(EV_KEY, 'turnstile');

    EnvConfigValidator::validateBootEnv();

    // Reaching here means no exception was raised.
    expect(true)->toBeTrue();
});

test('validateBootEnv throws naming the var + value for an invalid governed env var (3.2)', function () {
    evSetProcessEnv(EV_VAR, 'banana');
    Config::set(EV_KEY, 'banana'); // env resolved into config, fails in: rule

    try {
        EnvConfigValidator::validateBootEnv();
        $this->fail('Expected InvalidEnvironmentConfigException was not thrown.');
    } catch (InvalidEnvironmentConfigException $e) {
        expect($e->getMessage())->toContain(EV_VAR);
        expect($e->getMessage())->toContain('banana');
    }
});

test('validateBootEnv does not throw or warn when the governed env var is absent (3.4)', function () {
    evSetProcessEnv(EV_VAR, null);
    Config::set(EV_KEY, 'hcaptcha'); // config default; env absent

    Log::spy();

    EnvConfigValidator::validateBootEnv();

    // Absence is legal: no warning is logged for the absent var.
    Log::shouldNotHaveReceived('warning');
    expect(true)->toBeTrue();
});

test('validateBootEnv treats an empty-string env var as absent (3.4)', function () {
    evSetProcessEnv(EV_VAR, '');
    Config::set(EV_KEY, 'hcaptcha');

    // Empty string counts as absent, so an "invalid" empty value must NOT fatal.
    EnvConfigValidator::validateBootEnv();

    expect(true)->toBeTrue();
});

test('isValidEnvValue is true for a present, valid governed value (3.5)', function () {
    evSetProcessEnv(EV_VAR, 'cap');
    Config::set(EV_KEY, 'cap');

    expect(EnvConfigValidator::isValidEnvValue(EV_KEY))->toBeTrue();
});

test('isValidEnvValue is false for an invalid governed value (3.5)', function () {
    evSetProcessEnv(EV_VAR, 'banana');
    Config::set(EV_KEY, 'banana');

    expect(EnvConfigValidator::isValidEnvValue(EV_KEY))->toBeFalse();
});

test('isValidValue validates an arbitrary value against the shared rule (3.5)', function () {
    // Shared rule set reused by the write API: captcha.driver is in:hcaptcha,turnstile,cap.
    expect(EnvConfigValidator::isValidValue(EV_KEY, 'turnstile'))->toBeTrue();
    expect(EnvConfigValidator::isValidValue(EV_KEY, 'banana'))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| No-rule (warn-not-fatal) branch — honest note
|--------------------------------------------------------------------------
|
| Every key shipped in ConfigCacheService::KEYS declares a rule (enforced by
| KeyRegistryIntegrityTest), and KEYS is a const so it cannot be mutated at
| runtime to inject a rule-less governed key. We therefore exercise the
| no-rule branch of isValidEnvValue() directly: for a key that is NOT in the
| registry, ruleFor() returns null and isValidEnvValue() must return true
| (consistent with the warn-not-fatal boot behavior — Requirement 3.3).
|
*/
test('isValidEnvValue returns true when no rule is declared for the key (3.3)', function () {
    $unlistedKey = 'this.key.has.no.rule';

    expect(ConfigCacheService::ruleFor($unlistedKey))->toBeNull();
    expect(EnvConfigValidator::isValidEnvValue($unlistedKey))->toBeTrue();
});

test('isValidValue returns true when no rule is declared for the key (3.3)', function () {
    $unlistedKey = 'this.key.has.no.rule';

    expect(EnvConfigValidator::isValidValue($unlistedKey, 'anything-goes'))->toBeTrue();
});
