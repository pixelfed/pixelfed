<?php

use App\Models\ConfigCache as ConfigCacheModel;
use App\Services\ConfigCacheService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/*
| The DB-backed config cache used to be gated by instance.enable_cc
| (env ENABLE_CONFIG_CACHE): when off, config_cache()/ConfigCacheService::get()
| fell through to plain config(). That toggle was removed and the cache is now
| always on. These tests pin that: whether an admin previously set the flag
| true, false, or never set it at all, get() behaves identically (DB-backed),
| and a lingering instance.enable_cc value in their config has no effect.
*/

uses(LazilyRefreshDatabase::class);

// An ADMINONLY key (no env var) so the DB-cache read path is actually exercised.
const CC_ADMINONLY_KEY = 'uikit.custom.css';

// An env-bound key, used to confirm env precedence is unaffected by the flag.
const CC_ENVBOUND_KEY = 'filesystems.disks.s3.region';
const CC_ENVBOUND_VAR = 'AWS_DEFAULT_REGION';

function ccResetEnvRepository(): void
{
    $ref = new ReflectionClass(Env::class);
    $prop = $ref->getProperty('repository');
    $prop->setAccessible(true);
    $prop->setValue(null, null);
}

function ccSetProcessEnv(string $name, ?string $value): void
{
    if ($value === null) {
        putenv($name);
        unset($_ENV[$name], $_SERVER[$name]);
    } else {
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    ccResetEnvRepository();
}

function ccForget(string $key): void
{
    Cache::forget(ConfigCacheService::CACHE_KEY.$key);
}

beforeEach(function () {
    $this->ccOriginalEnv = [
        CC_ENVBOUND_VAR => getenv(CC_ENVBOUND_VAR),
    ];
    $this->ccOriginalConfig = [
        CC_ADMINONLY_KEY => Config::get(CC_ADMINONLY_KEY),
        CC_ENVBOUND_KEY => Config::get(CC_ENVBOUND_KEY),
        'instance.enable_cc' => Config::get('instance.enable_cc'),
    ];

    ccSetProcessEnv(CC_ENVBOUND_VAR, null);
    ccForget(CC_ADMINONLY_KEY);
    ccForget(CC_ENVBOUND_KEY);
    ConfigCacheModel::whereIn('k', [CC_ADMINONLY_KEY, CC_ENVBOUND_KEY])->delete();
});

afterEach(function () {
    foreach ($this->ccOriginalEnv as $var => $value) {
        ccSetProcessEnv($var, $value === false ? null : $value);
    }
    foreach ($this->ccOriginalConfig as $key => $value) {
        Config::set($key, $value);
    }
    ccResetEnvRepository();

    ccForget(CC_ADMINONLY_KEY);
    ccForget(CC_ENVBOUND_KEY);
});

// enable_cc = true (admin had opted in): the DB row wins over config(), same as always-on.
test('with instance.enable_cc=true a DB-written value wins over config() (admin previously enabled)', function () {
    Config::set('instance.enable_cc', true);
    Config::set(CC_ADMINONLY_KEY, '/* config default */');

    ConfigCacheService::put(CC_ADMINONLY_KEY, '.brand { color: red; }');

    expect(ConfigCacheService::get(CC_ADMINONLY_KEY))->toBe('.brand { color: red; }');
    expect(config_cache(CC_ADMINONLY_KEY))->toBe('.brand { color: red; }');
});

// enable_cc = false (admin had it off): the DB row STILL wins. Pre-removal this
// fell through to config(); the toggle no longer short-circuits the read.
test('with instance.enable_cc=false a DB-written value STILL wins (flag no longer disables the cache)', function () {
    Config::set('instance.enable_cc', false);
    Config::set(CC_ADMINONLY_KEY, '/* config default */');

    ConfigCacheService::put(CC_ADMINONLY_KEY, '.brand { color: blue; }');

    expect(ConfigCacheService::get(CC_ADMINONLY_KEY))->toBe('.brand { color: blue; }');
    expect(config_cache(CC_ADMINONLY_KEY))->toBe('.brand { color: blue; }');
});

// enable_cc never set (admin never touched the var): default behavior is on.
test('with instance.enable_cc unset a DB-written value wins (admin never configured the flag)', function () {
    Config::set('instance.enable_cc', null);
    Config::set(CC_ADMINONLY_KEY, '/* config default */');

    ConfigCacheService::put(CC_ADMINONLY_KEY, '.brand { color: green; }');

    expect(ConfigCacheService::get(CC_ADMINONLY_KEY))->toBe('.brand { color: green; }');
});

// The result is identical across all three prior-admin states for the same inputs.
test('get() returns the same DB-backed value regardless of the instance.enable_cc flag', function () {
    $results = [];

    foreach ([true, false, null] as $flag) {
        ccForget(CC_ADMINONLY_KEY);
        ConfigCacheModel::where('k', CC_ADMINONLY_KEY)->delete();

        Config::set('instance.enable_cc', $flag);
        Config::set(CC_ADMINONLY_KEY, '/* default */');
        ConfigCacheService::put(CC_ADMINONLY_KEY, '.same { color: black; }');

        $results[] = ConfigCacheService::get(CC_ADMINONLY_KEY);
    }

    expect($results)->each->toBe('.same { color: black; }');
    expect(array_unique($results))->toHaveCount(1);
});

// With no DB row, get() falls back to config() in every flag state (not gated by the flag).
test('get() falls back to config() when no DB row exists, in every enable_cc state', function () {
    foreach ([true, false, null] as $flag) {
        ccForget(CC_ADMINONLY_KEY);
        ConfigCacheModel::where('k', CC_ADMINONLY_KEY)->delete();

        Config::set('instance.enable_cc', $flag);
        Config::set(CC_ADMINONLY_KEY, '/* only in config */');

        expect(ConfigCacheService::get(CC_ADMINONLY_KEY))->toBe('/* only in config */');
    }
});

// Env precedence is independent of the removed flag: an env-locked key reads env
// whether enable_cc was on or off.
test('an env-locked key reads the env value regardless of the enable_cc flag', function () {
    ccSetProcessEnv(CC_ENVBOUND_VAR, 'us-east-1');
    Config::set(CC_ENVBOUND_KEY, 'us-east-1');

    // A drifted DB row that must be ignored because env wins.
    ConfigCacheService::putRaw(CC_ENVBOUND_KEY, 'eu-west-9');

    foreach ([true, false, null] as $flag) {
        ccForget(CC_ENVBOUND_KEY);
        Config::set('instance.enable_cc', $flag);

        expect(ConfigCacheService::isLocked(CC_ENVBOUND_KEY))->toBeTrue();
        expect(ConfigCacheService::get(CC_ENVBOUND_KEY))->toBe('us-east-1');
    }
});
