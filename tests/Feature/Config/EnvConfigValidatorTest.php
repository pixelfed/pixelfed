<?php

use App\Services\Config\EnvConfigValidator;
use App\Services\Config\InvalidEnvironmentConfigException;
use App\Services\ConfigCacheService;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

// A real ENVCONFIG key with an in: rule.
const EV_KEY = 'captcha.driver';
const EV_VAR = 'CAPTCHA_DRIVER';

// Reset Env's cached repository so the next Env::get() re-reads the process env.
function evResetEnvRepository(): void
{
    $ref = new ReflectionClass(Env::class);
    $prop = $ref->getProperty('repository');
    $prop->setAccessible(true);
    $prop->setValue(null, null);
}

// Set (or clear if null) an env var across every source, then reset the repository.
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

    // Start every governed env var absent so a stray .env value can't throw.

    foreach (ConfigCacheService::keysInList('ENVCONFIG') as $k) {
        evSetProcessEnv(ConfigCacheService::envVarFor($k), null);
    }
});

afterEach(function () {
    evSetProcessEnv(EV_VAR, $this->originalEnv === false ? null : $this->originalEnv);
    Config::set(EV_KEY, $this->originalConfig);
    evResetEnvRepository();
});

test('validateBootEnv does not throw for a present, valid governed env var', function () {
    evSetProcessEnv(EV_VAR, 'turnstile');
    Config::set(EV_KEY, 'turnstile');

    EnvConfigValidator::validateBootEnv();

    // Reaching here means no exception was raised.
    expect(true)->toBeTrue();
});

test('validateBootEnv throws naming the var + value for an invalid governed env var', function () {
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

test('validateBootEnv does not throw or warn when the governed env var is absent', function () {
    evSetProcessEnv(EV_VAR, null);
    Config::set(EV_KEY, 'hcaptcha'); // config default; env absent

    Log::spy();

    EnvConfigValidator::validateBootEnv();

    // Absence is legal: no warning is logged for the absent var.
    Log::shouldNotHaveReceived('warning');
    expect(true)->toBeTrue();
});

test('validateBootEnv treats an empty-string env var as absent', function () {
    evSetProcessEnv(EV_VAR, '');
    Config::set(EV_KEY, 'hcaptcha');

    // Empty string counts as absent, so an "invalid" empty value must NOT fatal.
    EnvConfigValidator::validateBootEnv();

    expect(true)->toBeTrue();
});

test('isValidEnvValue is true for a present, valid governed value', function () {
    evSetProcessEnv(EV_VAR, 'cap');
    Config::set(EV_KEY, 'cap');

    expect(EnvConfigValidator::isValidEnvValue(EV_KEY))->toBeTrue();
});

test('isValidEnvValue is false for an invalid governed value', function () {
    evSetProcessEnv(EV_VAR, 'banana');
    Config::set(EV_KEY, 'banana');

    expect(EnvConfigValidator::isValidEnvValue(EV_KEY))->toBeFalse();
});

test('isValidValue validates an arbitrary value against the shared rule', function () {
    // Shared rule set reused by the write API: captcha.driver is in:hcaptcha,turnstile,cap.
    expect(EnvConfigValidator::isValidValue(EV_KEY, 'turnstile'))->toBeTrue();
    expect(EnvConfigValidator::isValidValue(EV_KEY, 'banana'))->toBeFalse();
});

test('isValidEnvValue returns true when no rule is declared for the key', function () {
    $unlistedKey = 'this.key.has.no.rule';

    expect(ConfigCacheService::ruleFor($unlistedKey))->toBeNull();
    expect(EnvConfigValidator::isValidEnvValue($unlistedKey))->toBeTrue();
});

test('isValidValue returns true when no rule is declared for the key', function () {
    $unlistedKey = 'this.key.has.no.rule';

    expect(EnvConfigValidator::isValidValue($unlistedKey, 'anything-goes'))->toBeTrue();
});
