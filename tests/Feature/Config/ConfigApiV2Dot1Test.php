<?php

use App\Http\Controllers\Api\v2026\Admin\ConfigCacheController;
use App\Models\ConfigCache as ConfigCacheModel;
use App\Models\User;
use App\Services\ConfigCacheService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

const API_ADMINONLY_KEY = 'uikit.custom.css';

const API_ENVBOUND_KEY = 'filesystems.disks.s3.region';
const API_ENVBOUND_VAR = 'AWS_DEFAULT_REGION';

const API_ENVCONFIG_KEY = 'captcha.driver';
const API_ENVCONFIG_VAR = 'CAPTCHA_DRIVER';

const API_PROTECTED_KEY = 'captcha.hcaptcha.secret';
const API_PROTECTED_VAR = 'CAPTCHA_H_SECRET';

// Reset Env's cached repository so the next Env::get() re-reads the process env.
function apiResetEnvRepository(): void
{
    $ref = new ReflectionClass(Env::class);
    $prop = $ref->getProperty('repository');
    $prop->setAccessible(true);
    $prop->setValue(null, null);
}

// Set (or clear if null) an env var across every source, then reset the repository.
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

// Forget the memoized cache entry so the next get() re-resolves.
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

test('GET single returns the metadata item for a known ADMINONLY key', function () {
    apiAdmin(['admin:read']);
    Config::set(API_ADMINONLY_KEY, '/* default css */');

    $this->getJson('/api/v2.1/admin/config/'.API_ADMINONLY_KEY)
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['key', 'value', 'list', 'locked', 'source', 'protected'],
        ])
        ->assertJsonPath('data.key', API_ADMINONLY_KEY)
        ->assertJsonPath('data.list', 'ADMINONLY')
        ->assertJsonPath('data.locked', false)
        ->assertJsonPath('data.protected', false);
});

test('GET single for an unknown/uncached key returns 404, not a silent fallback', function () {
    apiAdmin(['admin:read']);

    $this->getJson('/api/v2.1/admin/config/this.key.is.not.cached')
        ->assertNotFound()
        ->assertJsonPath('key', 'this.key.is.not.cached');
});

test('GET bulk with no filter is rejected 422: keys are required', function () {
    apiAdmin(['admin:read']);

    $this->getJson('/api/v2.1/admin/config')
        ->assertStatus(422)
        ->assertJsonStructure(['message']);
});

test('GET bulk with an empty keys array is rejected 422', function () {
    apiAdmin(['admin:read']);

    $this->getJson('/api/v2.1/admin/config?keys[]=')
        ->assertStatus(422);
});

test('GET bulk with a ?keys[]= filter returns exactly the requested keys', function () {
    apiAdmin(['admin:read']);

    $response = $this->getJson('/api/v2.1/admin/config?keys[]='.API_ADMINONLY_KEY.'&keys[]='.API_ENVBOUND_KEY)
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $keys = collect($response->json('data'))->pluck('key')->all();
    expect($keys)->toContain(API_ADMINONLY_KEY);
    expect($keys)->toContain(API_ENVBOUND_KEY);
});

test('GET bulk with an unknown key in the filter returns 422 listing unknown_keys', function () {
    apiAdmin(['admin:read']);

    $this->getJson('/api/v2.1/admin/config?keys[]='.API_ADMINONLY_KEY.'&keys[]=this.key.is.not.cached')
        ->assertStatus(422)
        ->assertJsonPath('unknown_keys', ['this.key.is.not.cached']);
});

test('GET single masks a PROTECTED value', function () {
    apiAdmin(['admin:read']);

    // Env absent → the secret key is editable and the DB row wins.
    apiSetProcessEnv(API_PROTECTED_VAR, null);
    Config::set(API_PROTECTED_KEY, null);
    ConfigCacheService::putRaw(API_PROTECTED_KEY, 'super-secret-hcaptcha-value');
    apiForget(API_PROTECTED_KEY);

    $response = $this->getJson('/api/v2.1/admin/config/'.API_PROTECTED_KEY)
        ->assertOk()
        ->assertJsonPath('data.protected', true);

    $value = $response->json('data.value');
    // Masked: the raw secret must never appear, and the mask char must be present.
    expect($value)->not->toBe('super-secret-hcaptcha-value');
    expect($value)->toContain('*');
});

test('GET bulk for a non-admin (admin:read) returns 404', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $user->refresh();
    Passport::actingAs($user, ['admin:read']);

    $this->getJson('/api/v2.1/admin/config')->assertNotFound();
});

test('GET bulk for an admin WITHOUT the admin:read scope returns 404', function () {
    $admin = User::factory()->admin()->create();
    $admin->refresh();
    Passport::actingAs($admin, ['read']);

    $this->getJson('/api/v2.1/admin/config')->assertNotFound();
});

test('GET bulk unauthenticated returns 401', function () {
    $this->getJson('/api/v2.1/admin/config')->assertUnauthorized();
});

test('POST single valid write to an ADMINONLY key persists and reflects on read', function () {
    apiAdmin(['admin:write']);
    Config::set(API_ADMINONLY_KEY, '/* default css */');
    apiForget(API_ADMINONLY_KEY);

    $this->postJson('/api/v2.1/admin/config/'.API_ADMINONLY_KEY, [
        'value' => '.brand { color: red; }',
    ])
        ->assertOk()
        ->assertJsonPath('data.key', API_ADMINONLY_KEY)
        ->assertJsonPath('data.value', '.brand { color: red; }')
        ->assertJsonPath('changed.0.key', API_ADMINONLY_KEY);

    // Persisted and reflected on a subsequent read.
    expect(ConfigCacheModel::where('k', API_ADMINONLY_KEY)->value('v'))->toBe('.brand { color: red; }');

    Passport::actingAs(User::factory()->admin()->create()->fresh(), ['admin:read']);
    $this->getJson('/api/v2.1/admin/config/'.API_ADMINONLY_KEY)
        ->assertOk()
        ->assertJsonPath('data.value', '.brand { color: red; }');
});

test('POST single to an env-locked key (env present + valid) is rejected 422', function () {
    apiAdmin(['admin:write']);
    // s3.region is env-bound; with the env var present it is locked (env wins).
    apiSetProcessEnv(API_ENVBOUND_VAR, 'us-east-1');
    Config::set(API_ENVBOUND_KEY, 'us-east-1');

    $this->postJson('/api/v2.1/admin/config/'.API_ENVBOUND_KEY, [
        'value' => 'eu-west-9',
    ])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => [API_ENVBOUND_KEY]]);

    expect(ConfigCacheModel::where('k', API_ENVBOUND_KEY)->exists())->toBeFalse();
});

test('POST single to a locked ENVCONFIG key (env present + valid) is rejected 422 env-managed', function () {
    apiAdmin(['admin:write']);
    apiSetProcessEnv(API_ENVCONFIG_VAR, 'turnstile');
    Config::set(API_ENVCONFIG_KEY, 'turnstile');

    $this->postJson('/api/v2.1/admin/config/'.API_ENVCONFIG_KEY, [
        'value' => 'hcaptcha',
    ])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => [API_ENVCONFIG_KEY]]);

    expect(ConfigCacheModel::where('k', API_ENVCONFIG_KEY)->exists())->toBeFalse();
});

test('POST single to an unknown key is rejected 422', function () {
    apiAdmin(['admin:write']);

    $this->postJson('/api/v2.1/admin/config/this.key.is.not.cached', [
        'value' => 'x',
    ])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => ['this.key.is.not.cached']]);
});

test('POST single with a value failing its rule is rejected 422', function () {
    apiAdmin(['admin:write']);
    // Env absent → the key is editable; submit a value that fails its rule.
    apiSetProcessEnv(API_ENVCONFIG_VAR, null);
    Config::set(API_ENVCONFIG_KEY, 'hcaptcha');

    $this->postJson('/api/v2.1/admin/config/'.API_ENVCONFIG_KEY, [
        'value' => 'banana',
    ])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => [API_ENVCONFIG_KEY]]);

    expect(ConfigCacheModel::where('k', API_ENVCONFIG_KEY)->exists())->toBeFalse();
});

test('POST bulk is partial success: valid entries persist, invalid entries are reported', function () {
    apiAdmin(['admin:write']);
    Config::set(API_ADMINONLY_KEY, '/* default css */');
    apiForget(API_ADMINONLY_KEY);

    // s3.region is env-locked (env var present), so editing it is rejected.
    apiSetProcessEnv(API_ENVBOUND_VAR, 'us-east-1');
    Config::set(API_ENVBOUND_KEY, 'us-east-1');

    $this->postJson('/api/v2.1/admin/config', [
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

test('POST bulk with every entry invalid rejects 422 and persists nothing', function () {
    apiAdmin(['admin:write']);

    apiSetProcessEnv(API_ENVBOUND_VAR, 'us-east-1');
    Config::set(API_ENVBOUND_KEY, 'us-east-1');

    $this->postJson('/api/v2.1/admin/config', [
        'config' => [
            API_ENVBOUND_KEY => 'eu-west-9',                 // invalid: env-locked
            'this.key.is.not.cached' => 'x',                 // invalid: unknown
        ],
    ])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => [API_ENVBOUND_KEY, 'this.key.is.not.cached']]);

    expect(ConfigCacheModel::where('k', API_ENVBOUND_KEY)->exists())->toBeFalse();
});

test('POST bulk success returns ONLY the keys whose value actually changed', function () {
    apiAdmin(['admin:write']);

    // Submit one key unchanged and one changed → only the changed key is reported.
    Config::set(API_ADMINONLY_KEY, '/* default css */');
    ConfigCacheService::putRaw(API_ADMINONLY_KEY, '.same { color: blue; }');
    apiForget(API_ADMINONLY_KEY);

    // Second editable key: uikit.custom.js is ADMINONLY too.
    $secondKey = 'uikit.custom.js';
    apiForget($secondKey);

    $response = $this->postJson('/api/v2.1/admin/config', [
        'config' => [
            API_ADMINONLY_KEY => '.same { color: blue; }',  // unchanged
            $secondKey => 'console.log("new");',            // changed
        ],
    ])->assertOk();

    $changedKeys = collect($response->json('changed'))->pluck('key')->all();
    expect($changedKeys)->toBe([$secondKey]);
});

test('POST bulk with an empty value resets that key while writing the others', function () {
    apiAdmin(['admin:write']);

    // First key has a stored override we will reset via an empty value.
    Config::set(API_ADMINONLY_KEY, '/* default css */');
    ConfigCacheService::putRaw(API_ADMINONLY_KEY, '.override { color: red; }');
    apiForget(API_ADMINONLY_KEY);

    // Second ADMINONLY key gets a genuine new value in the same batch.
    $secondKey = 'uikit.custom.js';
    apiForget($secondKey);

    expect(ConfigCacheModel::where('k', API_ADMINONLY_KEY)->exists())->toBeTrue();

    $this->postJson('/api/v2.1/admin/config', [
        'config' => [
            API_ADMINONLY_KEY => '',                     // reset to default
            $secondKey => 'console.log("kept");',        // written
        ],
    ])->assertOk();

    // The reset key reverts to its config default; the other key is written.
    apiForget(API_ADMINONLY_KEY);
    expect(ConfigCacheService::get(API_ADMINONLY_KEY))->toBe('/* default css */');
    expect(ConfigCacheModel::where('k', $secondKey)->value('v'))->toBe('console.log("kept");');
});

test('POST bulk skips a PROTECTED masked placeholder but writes a real change', function () {
    apiAdmin(['admin:write']);

    // Protected key with a known stored secret (env absent so it is editable).
    apiSetProcessEnv(API_PROTECTED_VAR, null);
    Config::set(API_PROTECTED_KEY, null);
    ConfigCacheService::putRaw(API_PROTECTED_KEY, 'original-secret-value');
    apiForget(API_PROTECTED_KEY);

    // Second ADMINONLY key changes for real, in the same batch.
    Config::set(API_ADMINONLY_KEY, '/* default css */');
    apiForget(API_ADMINONLY_KEY);

    $masked = ConfigCacheController::maskProtectedConfig('original-secret-value');

    $response = $this->postJson('/api/v2.1/admin/config', [
        'config' => [
            API_PROTECTED_KEY => $masked,                 // untouched placeholder → skipped
            API_ADMINONLY_KEY => '.brand { color: red; }', // real change
        ],
    ])->assertOk();

    // The secret is unchanged; only the ADMINONLY key appears in `changed`.
    expect(decrypt(ConfigCacheModel::where('k', API_PROTECTED_KEY)->value('v')))->toBe('original-secret-value');
    $changedKeys = collect($response->json('changed'))->pluck('key')->all();
    expect($changedKeys)->toBe([API_ADMINONLY_KEY]);
});

test('POST write skips a PROTECTED masked value (no error, unchanged)', function () {
    apiAdmin(['admin:write']);

    // Editable protected key: env absent, seed a known value.
    apiSetProcessEnv(API_PROTECTED_VAR, null);
    Config::set(API_PROTECTED_KEY, null);
    ConfigCacheService::putRaw(API_PROTECTED_KEY, 'original-secret-value');
    apiForget(API_PROTECTED_KEY);

    // Resubmitting the exact masked placeholder → skipped, no change.
    $masked = ConfigCacheController::maskProtectedConfig('original-secret-value');

    $this->postJson('/api/v2.1/admin/config/'.API_PROTECTED_KEY, [
        'value' => $masked,
    ])
        ->assertOk()
        ->assertJsonPath('changed', []);

    // Stored value unchanged.
    $stored = ConfigCacheModel::where('k', API_PROTECTED_KEY)->value('v');
    expect(decrypt($stored))->toBe('original-secret-value');
});

test('POST write to a PROTECTED key accepts a real secret that contains asterisks', function () {
    apiAdmin(['admin:write']);

    apiSetProcessEnv(API_PROTECTED_VAR, null);
    Config::set(API_PROTECTED_KEY, null);
    ConfigCacheService::putRaw(API_PROTECTED_KEY, 'original-secret-value');
    apiForget(API_PROTECTED_KEY);

    // A real secret containing '*' must not be mistaken for the mask.
    $newSecret = 'p@ss*w0rd*with*stars';

    $this->postJson('/api/v2.1/admin/config/'.API_PROTECTED_KEY, [
        'value' => $newSecret,
    ])->assertOk();

    $stored = ConfigCacheModel::where('k', API_PROTECTED_KEY)->value('v');
    expect(decrypt($stored))->toBe($newSecret);
});

test('POST write treats a short secret masked placeholder as unchanged', function () {
    apiAdmin(['admin:write']);

    // A short secret (<8 chars) masks to all-asterisks; resubmitting it is a skip.
    apiSetProcessEnv(API_PROTECTED_VAR, null);
    Config::set(API_PROTECTED_KEY, null);
    ConfigCacheService::putRaw(API_PROTECTED_KEY, 'short');
    apiForget(API_PROTECTED_KEY);

    $masked = ConfigCacheController::maskProtectedConfig('short'); // "*****"

    $this->postJson('/api/v2.1/admin/config/'.API_PROTECTED_KEY, [
        'value' => $masked,
    ])
        ->assertOk()
        ->assertJsonPath('changed', []);

    expect(decrypt(ConfigCacheModel::where('k', API_PROTECTED_KEY)->value('v')))->toBe('short');
});

test('POST write does NOT skip a masked-looking value when the secret has no current value', function () {
    apiAdmin(['admin:write']);

    // No current value → no placeholder to match, so a masked-looking value is written.
    apiSetProcessEnv(API_PROTECTED_VAR, null);
    Config::set(API_PROTECTED_KEY, '');
    ConfigCacheModel::where('k', API_PROTECTED_KEY)->delete();
    apiForget(API_PROTECTED_KEY);

    $this->postJson('/api/v2.1/admin/config/'.API_PROTECTED_KEY, [
        'value' => '****',
    ])->assertOk();

    // It was persisted, not skipped.
    expect(decrypt(ConfigCacheModel::where('k', API_PROTECTED_KEY)->value('v')))->toBe('****');
});

test('POST write with an empty value resets the key: the DB row is cleared', function () {
    apiAdmin(['admin:write']);

    // Seed a stored override, then submit an empty value to reset it.
    Config::set(API_ADMINONLY_KEY, '/* default css */');
    ConfigCacheService::putRaw(API_ADMINONLY_KEY, '.override { color: red; }');
    apiForget(API_ADMINONLY_KEY);

    expect(ConfigCacheModel::where('k', API_ADMINONLY_KEY)->exists())->toBeTrue();

    $this->postJson('/api/v2.1/admin/config/'.API_ADMINONLY_KEY, [
        'value' => '',
    ])->assertOk();

    // The override is cleared → the key reverts to its config default.
    apiForget(API_ADMINONLY_KEY);
    expect(ConfigCacheService::get(API_ADMINONLY_KEY))->toBe('/* default css */');
});

test('POST write with an admin:read-only token returns 404 (needs admin:write)', function () {
    apiAdmin(['admin:read']);

    $this->postJson('/api/v2.1/admin/config/'.API_ADMINONLY_KEY, [
        'value' => '.x { color: red; }',
    ])->assertNotFound();
});
