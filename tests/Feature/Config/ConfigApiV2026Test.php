<?php

use App\Models\ConfigCache as ConfigCacheModel;
use App\Models\User;
use App\Services\ConfigCacheService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| /api/v2026/admin/config read + write API (Task 14)
|--------------------------------------------------------------------------
|
| Exercises the versioned admin config API end to end:
|   GET  /api/v2026/admin/config          index  (bulk; optional ?keys[]=)
|   GET  /api/v2026/admin/config/{key}    show   (single; dotted keys)
|   POST /api/v2026/admin/config          store  (bulk; { config: { key: value } })
|   POST /api/v2026/admin/config/{key}    update (single; { value: ... })
|
| Auth mirrors the existing admin API tests (ScopeTest / AdminAccessTest):
| Passport::actingAs($admin, ['admin:read'|'admin:write']). The guard returns
| 404 (not 403) for a non-admin or a wrong-scope token to avoid revealing the
| endpoints; unauthenticated (no token) → 401 from the auth middleware.
|
| Real KEYS keys are reused so the test tracks the live registry:
|   ADMINONLY  : uikit.custom.css               (no env; always editable)
|   ENVBOUND    : filesystems.disks.s3.region    (AWS_DEFAULT_REGION; never editable)
|   ENVCONFIG: captcha.driver                 (CAPTCHA_DRIVER; in:hcaptcha,turnstile,cap)
|   PROTECTED  : captcha.hcaptcha.secret        (CAPTCHA_H_SECRET; ENVCONFIG + secret)
|
| Env presence is driven exactly like ReadWritePrecedenceTest: mutate the
| process env across every source the Env repository reads, then reset the
| cached (immutable) repository via reflection so the next Env::get() re-reads.
|
| Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 6.1, 6.2, 6.3, 6.4,
| 6.5, 6.6, 6.7, 6.8
|
*/

const API_ADMINONLY_KEY = 'uikit.custom.css';

const API_ENVBOUND_KEY = 'filesystems.disks.s3.region';
const API_ENVBOUND_VAR = 'AWS_DEFAULT_REGION';

const API_ENVCONFIG_KEY = 'captcha.driver';
const API_ENVCONFIG_VAR = 'CAPTCHA_DRIVER';

const API_PROTECTED_KEY = 'captcha.hcaptcha.secret';
const API_PROTECTED_VAR = 'CAPTCHA_H_SECRET';

/**
 * Reset Illuminate\Support\Env's cached (immutable) repository so the next
 * Env::get() re-reads the process environment.
 */
function apiResetEnvRepository(): void
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
function apiSetProcessEnv(string $name, ?string $value): void
{
    if ($value === null) {
        putenv($name);
        unset($_ENV[$name], $_SERVER[$name]);
    } else {
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    apiResetEnvRepository();
}

/**
 * Forget the memoized 12h cache entry for a key so the next get() re-resolves
 * from the DB row / config fallback.
 */
function apiForget(string $key): void
{
    Cache::forget(ConfigCacheService::CACHE_KEY.$key);
}

function apiAdmin(array $scopes): User
{
    $admin = User::factory()->admin()->create();
    $admin->refresh();
    Passport::actingAs($admin, $scopes);

    return $admin;
}

beforeEach(function () {
    $this->originalEnv = [
        API_ENVBOUND_VAR => getenv(API_ENVBOUND_VAR),
        API_ENVCONFIG_VAR => getenv(API_ENVCONFIG_VAR),
        API_PROTECTED_VAR => getenv(API_PROTECTED_VAR),
    ];
    $this->originalConfig = [
        API_ADMINONLY_KEY => Config::get(API_ADMINONLY_KEY),
        API_ENVBOUND_KEY => Config::get(API_ENVBOUND_KEY),
        API_ENVCONFIG_KEY => Config::get(API_ENVCONFIG_KEY),
        API_PROTECTED_KEY => Config::get(API_PROTECTED_KEY),
    ];

    // Start each case from a known env-absent baseline.
    foreach (array_keys($this->originalEnv) as $var) {
        apiSetProcessEnv($var, null);
    }
});

afterEach(function () {
    foreach ($this->originalEnv as $var => $value) {
        apiSetProcessEnv($var, $value === false ? null : $value);
    }
    foreach ($this->originalConfig as $key => $value) {
        Config::set($key, $value);
    }
    apiResetEnvRepository();

    foreach ([API_ADMINONLY_KEY, API_ENVBOUND_KEY, API_ENVCONFIG_KEY, API_PROTECTED_KEY] as $key) {
        apiForget($key);
    }
});

/*
|--------------------------------------------------------------------------
| Read — single (GET /api/v2026/admin/config/{key})
|--------------------------------------------------------------------------
*/

test('GET single returns the metadata item for a known ADMINONLY key (5.2)', function () {
    apiAdmin(['admin:read']);
    Config::set(API_ADMINONLY_KEY, '/* default css */');

    $this->getJson('/api/v2026/admin/config/'.API_ADMINONLY_KEY)
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['key', 'value', 'list', 'locked', 'source', 'protected'],
        ])
        ->assertJsonPath('data.key', API_ADMINONLY_KEY)
        ->assertJsonPath('data.list', 'ADMINONLY')
        ->assertJsonPath('data.locked', false)
        ->assertJsonPath('data.protected', false);
});

test('GET single for an unknown/uncached key returns 404, not a silent fallback (5.5)', function () {
    apiAdmin(['admin:read']);

    $this->getJson('/api/v2026/admin/config/this.key.is.not.cached')
        ->assertNotFound()
        ->assertJsonPath('key', 'this.key.is.not.cached');
});

/*
|--------------------------------------------------------------------------
| Read — bulk (GET /api/v2026/admin/config)
|--------------------------------------------------------------------------
*/

test('GET bulk with no filter is rejected 422: keys are required (5.3)', function () {
    apiAdmin(['admin:read']);

    $this->getJson('/api/v2026/admin/config')
        ->assertStatus(422)
        ->assertJsonStructure(['message']);
});

test('GET bulk with an empty keys array is rejected 422 (5.3b)', function () {
    apiAdmin(['admin:read']);

    $this->getJson('/api/v2026/admin/config?keys[]=')
        ->assertStatus(422);
});

test('GET bulk with a ?keys[]= filter returns exactly the requested keys (5.4)', function () {
    apiAdmin(['admin:read']);

    $response = $this->getJson('/api/v2026/admin/config?keys[]='.API_ADMINONLY_KEY.'&keys[]='.API_ENVBOUND_KEY)
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $keys = collect($response->json('data'))->pluck('key')->all();
    expect($keys)->toContain(API_ADMINONLY_KEY);
    expect($keys)->toContain(API_ENVBOUND_KEY);
});

test('GET bulk with an unknown key in the filter returns 422 listing unknown_keys (5.5)', function () {
    apiAdmin(['admin:read']);

    $this->getJson('/api/v2026/admin/config?keys[]='.API_ADMINONLY_KEY.'&keys[]=this.key.is.not.cached')
        ->assertStatus(422)
        ->assertJsonPath('unknown_keys', ['this.key.is.not.cached']);
});

/*
|--------------------------------------------------------------------------
| Read — masked protected value (5.6)
|--------------------------------------------------------------------------
*/

test('GET single masks a PROTECTED value (5.6)', function () {
    apiAdmin(['admin:read']);

    // captcha.hcaptcha.secret is ENVCONFIG + secret; keep its env ABSENT so
    // the key is editable and the DB row wins, then seed a real value.
    apiSetProcessEnv(API_PROTECTED_VAR, null);
    Config::set(API_PROTECTED_KEY, null);
    ConfigCacheService::putRaw(API_PROTECTED_KEY, 'super-secret-hcaptcha-value');
    apiForget(API_PROTECTED_KEY);

    $response = $this->getJson('/api/v2026/admin/config/'.API_PROTECTED_KEY)
        ->assertOk()
        ->assertJsonPath('data.protected', true);

    $value = $response->json('data.value');
    // Masked: the raw secret must never appear, and the mask char must be present.
    expect($value)->not->toBe('super-secret-hcaptcha-value');
    expect($value)->toContain('*');
});

/*
|--------------------------------------------------------------------------
| Read — auth
|--------------------------------------------------------------------------
*/

test('GET bulk for a non-admin (admin:read) returns 404', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $user->refresh();
    Passport::actingAs($user, ['admin:read']);

    $this->getJson('/api/v2026/admin/config')->assertNotFound();
});

test('GET bulk for an admin WITHOUT the admin:read scope returns 404', function () {
    $admin = User::factory()->admin()->create();
    $admin->refresh();
    Passport::actingAs($admin, ['read']);

    $this->getJson('/api/v2026/admin/config')->assertNotFound();
});

test('GET bulk unauthenticated returns 401', function () {
    $this->getJson('/api/v2026/admin/config')->assertUnauthorized();
});

/*
|--------------------------------------------------------------------------
| Write — single (POST /api/v2026/admin/config/{key})
|--------------------------------------------------------------------------
*/

test('POST single valid write to an ADMINONLY key persists and reflects on read (6.1, 6.5, 6.8)', function () {
    apiAdmin(['admin:write']);
    Config::set(API_ADMINONLY_KEY, '/* default css */');
    apiForget(API_ADMINONLY_KEY);

    $this->postJson('/api/v2026/admin/config/'.API_ADMINONLY_KEY, [
        'value' => '.brand { color: red; }',
    ])
        ->assertOk()
        ->assertJsonPath('data.key', API_ADMINONLY_KEY)
        ->assertJsonPath('data.value', '.brand { color: red; }')
        ->assertJsonPath('changed.0.key', API_ADMINONLY_KEY);

    // Persisted and reflected on a subsequent read.
    expect(ConfigCacheModel::where('k', API_ADMINONLY_KEY)->value('v'))->toBe('.brand { color: red; }');

    Passport::actingAs(User::factory()->admin()->create()->fresh(), ['admin:read']);
    $this->getJson('/api/v2026/admin/config/'.API_ADMINONLY_KEY)
        ->assertOk()
        ->assertJsonPath('data.value', '.brand { color: red; }');
});

test('POST single to an env-locked key (env present + valid) is rejected 422 (6.3)', function () {
    apiAdmin(['admin:write']);
    // s3.region is env-bound; with the env var present it is locked (env wins).
    apiSetProcessEnv(API_ENVBOUND_VAR, 'us-east-1');
    Config::set(API_ENVBOUND_KEY, 'us-east-1');

    $this->postJson('/api/v2026/admin/config/'.API_ENVBOUND_KEY, [
        'value' => 'eu-west-9',
    ])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => [API_ENVBOUND_KEY]]);

    expect(ConfigCacheModel::where('k', API_ENVBOUND_KEY)->exists())->toBeFalse();
});

test('POST single to a locked ENVCONFIG key (env present + valid) is rejected 422 env-managed (6.4)', function () {
    apiAdmin(['admin:write']);
    apiSetProcessEnv(API_ENVCONFIG_VAR, 'turnstile');
    Config::set(API_ENVCONFIG_KEY, 'turnstile');

    $this->postJson('/api/v2026/admin/config/'.API_ENVCONFIG_KEY, [
        'value' => 'hcaptcha',
    ])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => [API_ENVCONFIG_KEY]]);

    expect(ConfigCacheModel::where('k', API_ENVCONFIG_KEY)->exists())->toBeFalse();
});

test('POST single to an unknown key is rejected 422 (6.6)', function () {
    apiAdmin(['admin:write']);

    $this->postJson('/api/v2026/admin/config/this.key.is.not.cached', [
        'value' => 'x',
    ])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => ['this.key.is.not.cached']]);
});

test('POST single with a value failing its rule is rejected 422 (6.1)', function () {
    apiAdmin(['admin:write']);
    // captcha.driver is ENVCONFIG (in:hcaptcha,turnstile,cap). Keep env ABSENT
    // so the key is editable, then submit an invalid value.
    apiSetProcessEnv(API_ENVCONFIG_VAR, null);
    Config::set(API_ENVCONFIG_KEY, 'hcaptcha');

    $this->postJson('/api/v2026/admin/config/'.API_ENVCONFIG_KEY, [
        'value' => 'banana',
    ])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => [API_ENVCONFIG_KEY]]);

    expect(ConfigCacheModel::where('k', API_ENVCONFIG_KEY)->exists())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Write — bulk (POST /api/v2026/admin/config)
|--------------------------------------------------------------------------
*/

test('POST bulk is partial success: valid entries persist, invalid entries are reported (6.2)', function () {
    apiAdmin(['admin:write']);
    Config::set(API_ADMINONLY_KEY, '/* default css */');
    apiForget(API_ADMINONLY_KEY);

    // s3.region is env-locked (env var present), so editing it is rejected.
    apiSetProcessEnv(API_ENVBOUND_VAR, 'us-east-1');
    Config::set(API_ENVBOUND_KEY, 'us-east-1');

    $this->postJson('/api/v2026/admin/config', [
        'config' => [
            API_ADMINONLY_KEY => '.valid { color: green; }',   // valid
            API_ENVBOUND_KEY => 'eu-west-9',                     // invalid: env-locked
        ],
    ])
        ->assertOk()
        ->assertJsonStructure(['changed', 'errors' => [API_ENVBOUND_KEY]]);

    // The VALID entry IS written; the invalid one is not.
    expect(ConfigCacheModel::where('k', API_ADMINONLY_KEY)->exists())->toBeTrue();
    expect(ConfigCacheModel::where('k', API_ENVBOUND_KEY)->exists())->toBeFalse();
});

test('POST bulk with every entry invalid rejects 422 and persists nothing (6.2b)', function () {
    apiAdmin(['admin:write']);

    apiSetProcessEnv(API_ENVBOUND_VAR, 'us-east-1');
    Config::set(API_ENVBOUND_KEY, 'us-east-1');

    $this->postJson('/api/v2026/admin/config', [
        'config' => [
            API_ENVBOUND_KEY => 'eu-west-9',                 // invalid: env-locked
            'this.key.is.not.cached' => 'x',                 // invalid: unknown
        ],
    ])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => [API_ENVBOUND_KEY, 'this.key.is.not.cached']]);

    expect(ConfigCacheModel::where('k', API_ENVBOUND_KEY)->exists())->toBeFalse();
});

test('POST bulk success returns ONLY the keys whose value actually changed (6.8)', function () {
    apiAdmin(['admin:write']);

    // Seed the ADMINONLY key to a known current value; submit it unchanged plus
    // a second key with a NEW value → only the changed key appears in `changed`.
    Config::set(API_ADMINONLY_KEY, '/* default css */');
    ConfigCacheService::putRaw(API_ADMINONLY_KEY, '.same { color: blue; }');
    apiForget(API_ADMINONLY_KEY);

    // Second editable key: uikit.custom.js is ADMINONLY too.
    $secondKey = 'uikit.custom.js';
    apiForget($secondKey);

    $response = $this->postJson('/api/v2026/admin/config', [
        'config' => [
            API_ADMINONLY_KEY => '.same { color: blue; }',  // unchanged
            $secondKey => 'console.log("new");',            // changed
        ],
    ])->assertOk();

    $changedKeys = collect($response->json('changed'))->pluck('key')->all();
    expect($changedKeys)->toBe([$secondKey]);
});

test('POST write skips a PROTECTED masked/empty value (no error, unchanged) (6.7)', function () {
    apiAdmin(['admin:write']);

    // Editable protected key: env absent, seed a known value.
    apiSetProcessEnv(API_PROTECTED_VAR, null);
    Config::set(API_PROTECTED_KEY, null);
    ConfigCacheService::putRaw(API_PROTECTED_KEY, 'original-secret-value');
    apiForget(API_PROTECTED_KEY);

    // Submit a masked placeholder (contains '*') → skipped, no error, no change.
    $this->postJson('/api/v2026/admin/config/'.API_PROTECTED_KEY, [
        'value' => 'or******ue',
    ])
        ->assertOk()
        ->assertJsonPath('changed', []);

    // Stored value unchanged.
    $stored = ConfigCacheModel::where('k', API_PROTECTED_KEY)->value('v');
    expect(decrypt($stored))->toBe('original-secret-value');
});

/*
|--------------------------------------------------------------------------
| Write — auth
|--------------------------------------------------------------------------
*/

test('POST write with an admin:read-only token returns 404 (needs admin:write)', function () {
    apiAdmin(['admin:read']);

    $this->postJson('/api/v2026/admin/config/'.API_ADMINONLY_KEY, [
        'value' => '.x { color: red; }',
    ])->assertNotFound();
});
