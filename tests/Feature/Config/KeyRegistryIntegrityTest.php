<?php

use App\Services\ConfigCacheService;

const VALID_LISTS = ['ENVCONFIG', 'ADMINONLY'];

test('every KEYS entry resolves to exactly one valid list', function () {
    foreach (array_keys(ConfigCacheService::KEYS) as $key) {
        expect(ConfigCacheService::listOf($key))->toBeIn(
            VALID_LISTS,
            "key [{$key}] must resolve to exactly one valid list"
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

test('every ADMINONLY key has NO env binding', function () {
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

test('ENVONLY has been fully removed from the registry', function () {
    expect(ConfigCacheService::keysInList('ENVONLY'))->toBeEmpty();

    foreach (array_keys(ConfigCacheService::KEYS) as $key) {
        expect(ConfigCacheService::listOf($key))->not->toBe(
            'ENVONLY',
            "key [{$key}] still resolves to the removed ENVONLY list; use ENVCONFIG."
        );
    }
});

test('list is derived from env binding: ENVCONFIG iff an env var is declared, ADMINONLY iff not', function () {
    foreach (array_keys(ConfigCacheService::KEYS) as $key) {
        $list = ConfigCacheService::listOf($key);
        $hasEnv = ConfigCacheService::envVarFor($key) !== null;

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

test('an unregistered key is uncached and resolves to no list', function () {
    expect(ConfigCacheService::isCached('this.key.is.not.cached'))->toBeFalse();
    expect(ConfigCacheService::listOf('this.key.is.not.cached'))->toBeNull();
});
