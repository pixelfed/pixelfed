<?php

use App\Services\ConfigCacheService;

/*
|--------------------------------------------------------------------------
| Developer-time guard: ConfigCacheService::KEYS integrity
|--------------------------------------------------------------------------
|
| The unified KEYS registry replaces the old flat allow-list + separate
| ENV_MAP. There is no separate ENV_MAP — the env binding lives inside each
| KEYS entry's `env` field. These tests enforce the invariants that keep the
| three lists mutually exclusive and internally consistent:
|
|   - every entry declares exactly one valid list (ENVCONFIG | ADMINONLY);
|   - the per-list partition (keysInList) covers every key with no overlap;
|   - every ENVCONFIG key has a non-empty env binding, and every ADMINONLY
|     key has NO env binding (Requirement 13.4);
|   - every ENVCONFIG key declares a validation rule.
|
| Validates: Requirements 1.3, 13.4
|
*/

const VALID_LISTS = ['ENVCONFIG', 'ADMINONLY'];

test('every KEYS entry declares exactly one valid list', function () {
    foreach (ConfigCacheService::KEYS as $key => $meta) {
        expect($meta['list'] ?? null)->toBeIn(
            VALID_LISTS,
            "key [{$key}] must declare exactly one valid list"
        );
    }
});

test('keysInList partitions KEYS cleanly: no overlap and full coverage', function () {
    $partitioned = [];
    foreach (VALID_LISTS as $list) {
        foreach (ConfigCacheService::keysInList($list) as $key) {
            // No key may appear in more than one list.
            expect($partitioned)->not->toHaveKey(
                $key,
                "key [{$key}] appears in more than one list"
            );
            $partitioned[$key] = $list;
        }
    }

    // Every registered key is accounted for by exactly one list (full coverage).
    $registered = array_keys(ConfigCacheService::KEYS);
    sort($registered);
    $covered = array_keys($partitioned);
    sort($covered);

    expect($covered)->toEqual($registered);
});

test('every ENVCONFIG key has a non-empty env binding', function () {
    $envKeys = ConfigCacheService::keysInList('ENVCONFIG');

    expect($envKeys)->not->toBeEmpty();

    foreach ($envKeys as $key) {
        $env = ConfigCacheService::envVarFor($key);
        expect($env)->toBeString("key [{$key}] must declare an env binding");
        expect(trim($env))->not->toBe('', "key [{$key}] has an empty env binding");
    }
});

test('every ADMINONLY key has NO env binding (Requirement 13.4)', function () {
    $adminKeys = ConfigCacheService::keysInList('ADMINONLY');

    expect($adminKeys)->not->toBeEmpty();

    foreach ($adminKeys as $key) {
        expect(ConfigCacheService::envVarFor($key))->toBeNull(
            "ADMINONLY key [{$key}] must not declare an env binding"
        );
    }
});

test('every ENVCONFIG key declares a validation rule', function () {
    $envKeys = ConfigCacheService::keysInList('ENVCONFIG');

    foreach ($envKeys as $key) {
        $rule = ConfigCacheService::ruleFor($key);
        expect($rule)->toBeString("key [{$key}] must declare a validation rule");
        expect(trim($rule))->not->toBe('', "key [{$key}] has an empty validation rule");
    }
});

/*
|--------------------------------------------------------------------------
| Only two lists exist: ENVCONFIG (env-bound) and ADMINONLY (no env var)
|--------------------------------------------------------------------------
|
| ENVONLY was removed — every env-bound key now follows the ENVCONFIG rule.
| The invariant that ties list to env binding:
|   - ENVCONFIG  ⇔ the key declares an env binding
|   - ADMINONLY    ⇔ the key declares NO env binding
|
| This guard fails if the (now-removed) ENVONLY label reappears, or if a key's
| list and env-binding disagree.
*/

test('ENVONLY has been fully removed from the registry', function () {
    expect(ConfigCacheService::keysInList('ENVONLY'))->toBeEmpty();

    foreach (ConfigCacheService::KEYS as $key => $meta) {
        expect($meta['list'] ?? null)->not->toBe(
            'ENVONLY',
            "key [{$key}] still uses the removed ENVONLY list; use ENVCONFIG."
        );
    }
});

test('list matches env binding: ENVCONFIG iff an env var is declared, ADMINONLY iff not', function () {
    foreach (ConfigCacheService::KEYS as $key => $meta) {
        $list = $meta['list'] ?? null;
        $hasEnv = ($meta['env'] ?? null) !== null;

        if ($hasEnv) {
            expect($list)->toBe(
                'ENVCONFIG',
                "key [{$key}] declares an env binding, so it must be ENVCONFIG."
            );
        } else {
            expect($list)->toBe(
                'ADMINONLY',
                "key [{$key}] declares no env binding, so it must be ADMINONLY."
            );
        }
    }
});
