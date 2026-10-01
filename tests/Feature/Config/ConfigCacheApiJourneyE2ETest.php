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

const E2E2_ADMINONLY_KEY = 'uikit.custom.css';

const E2E2_ENVBOUND_KEY = 'filesystems.disks.s3.region';
const E2E2_ENVBOUND_VAR = 'AWS_DEFAULT_REGION';

const E2E2_ENVCONFIG_KEY = 'captcha.driver';
const E2E2_ENVCONFIG_VAR = 'CAPTCHA_DRIVER';

const E2E2_PROTECTED_KEY = 'captcha.hcaptcha.secret';
const E2E2_PROTECTED_VAR = 'CAPTCHA_H_SECRET';

const E2E2_MARKER_KEY = 'config-cache:sync-hash';
const E2E2_LOCK_KEY = 'config-cache:sync';

// Reset Env's cached repository so the next Env::get() re-reads the process env.
function e2e2ResetEnvRepository(): void
{
    $ref = new ReflectionClass(Env::class);
    $prop = $ref->getProperty('repository');
    $prop->setAccessible(true);
    $prop->setValue(null, null);
}

// Set (or clear if null) an env var across every source, then reset the repository.
function e2e2SetProcessEnv(string $name, ?string $value): void
{
    if ($value === null) {
        putenv($name);
        unset($_ENV[$name], $_SERVER[$name]);
    } else {
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    e2e2ResetEnvRepository();
}

// Forget the memoized cache entry so the next get() re-resolves.
function e2e2Forget(string $key): void
{
    Cache::forget(ConfigCacheService::CACHE_KEY.$key);
}

// Authenticate as an admin with the given Passport scopes.
function e2e2Admin(array $scopes): User
{
    $admin = User::factory()->admin()->create();
    $admin->refresh();
    Passport::actingAs($admin, $scopes);

    return $admin;
}

beforeEach(function () {
    $this->originalEnv = [
        E2E2_ENVBOUND_VAR => getenv(E2E2_ENVBOUND_VAR),
        E2E2_ENVCONFIG_VAR => getenv(E2E2_ENVCONFIG_VAR),
        E2E2_PROTECTED_VAR => getenv(E2E2_PROTECTED_VAR),
        'PIXELFED_CONFIG_CACHE_SYNC' => getenv('PIXELFED_CONFIG_CACHE_SYNC'),
    ];
    $this->originalConfig = [
        E2E2_ADMINONLY_KEY => Config::get(E2E2_ADMINONLY_KEY),
        E2E2_ENVBOUND_KEY => Config::get(E2E2_ENVBOUND_KEY),
        E2E2_ENVCONFIG_KEY => Config::get(E2E2_ENVCONFIG_KEY),
        E2E2_PROTECTED_KEY => Config::get(E2E2_PROTECTED_KEY),
    ];

    // Start each case from a known env-absent baseline.
    foreach ([E2E2_ENVBOUND_VAR, E2E2_ENVCONFIG_VAR, E2E2_PROTECTED_VAR, 'PIXELFED_CONFIG_CACHE_SYNC'] as $var) {
        e2e2SetProcessEnv($var, null);
    }

    Cache::forget(E2E2_MARKER_KEY);
    Cache::forget(E2E2_LOCK_KEY);
    foreach ([E2E2_ADMINONLY_KEY, E2E2_ENVBOUND_KEY, E2E2_ENVCONFIG_KEY, E2E2_PROTECTED_KEY] as $key) {
        e2e2Forget($key);
    }
});

afterEach(function () {
    foreach ($this->originalEnv as $var => $value) {
        e2e2SetProcessEnv($var, $value === false ? null : $value);
    }
    foreach ($this->originalConfig as $key => $value) {
        Config::set($key, $value);
    }
    e2e2ResetEnvRepository();

    Cache::forget(E2E2_MARKER_KEY);
    Cache::forget(E2E2_LOCK_KEY);
    foreach ([E2E2_ADMINONLY_KEY, E2E2_ENVBOUND_KEY, E2E2_ENVCONFIG_KEY, E2E2_PROTECTED_KEY] as $key) {
        e2e2Forget($key);
    }
});

// Journey 1: ADMINONLY write-then-read round-trip chained across endpoints,
// then a reset-to-default via an empty value, all over the HTTP API.
test('journey: ADMINONLY write then read across single+bulk endpoints then reset to default', function () {
    e2e2Admin(['admin:write']);
    Config::set(E2E2_ADMINONLY_KEY, '/* default css */');
    e2e2Forget(E2E2_ADMINONLY_KEY);

    // Step 1: POST single update writes the override.
    $this->postJson('/api/v2.1/admin/config/'.E2E2_ADMINONLY_KEY, [
        'value' => '.brand { color: teal; }',
    ])
        ->assertOk()
        ->assertJsonPath('data.value', '.brand { color: teal; }')
        ->assertJsonPath('changed.0.key', E2E2_ADMINONLY_KEY);

    // DB reflects the write.
    expect(ConfigCacheModel::where('k', E2E2_ADMINONLY_KEY)->value('v'))->toBe('.brand { color: teal; }');

    // Step 2: a read-scope admin GETs single and sees db-sourced metadata.
    e2e2Admin(['admin:read']);
    $this->getJson('/api/v2.1/admin/config/'.E2E2_ADMINONLY_KEY)
        ->assertOk()
        ->assertJsonPath('data.value', '.brand { color: teal; }')
        ->assertJsonPath('data.source', 'db')
        ->assertJsonPath('data.locked', false)
        ->assertJsonPath('data.list', 'ADMINONLY');

    // Step 3: GET bulk returns the same key with the same shape.
    $this->getJson('/api/v2.1/admin/config?keys[]='.E2E2_ADMINONLY_KEY)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.value', '.brand { color: teal; }')
        ->assertJsonPath('data.0.source', 'db')
        ->assertJsonPath('data.0.list', 'ADMINONLY');

    // Step 4: POST single with an empty value resets the override. The stored
    // override is cleared; because the config default is non-empty the key is
    // re-seeded from the default, so the effective value reverts to the default.
    e2e2Admin(['admin:write']);
    $this->postJson('/api/v2.1/admin/config/'.E2E2_ADMINONLY_KEY, [
        'value' => '',
    ])->assertOk();

    e2e2Forget(E2E2_ADMINONLY_KEY);
    expect(ConfigCacheService::get(E2E2_ADMINONLY_KEY))->toBe('/* default css */');

    // Step 5: a subsequent GET reflects the config default value end to end.
    e2e2Admin(['admin:read']);
    $this->getJson('/api/v2.1/admin/config/'.E2E2_ADMINONLY_KEY)
        ->assertOk()
        ->assertJsonPath('data.value', '/* default css */');
});

// Journey 2: an env-bound key writable while the env is absent, then env wins
// once the var is present+valid — a full lock journey over the HTTP API.
test('journey: ENVBOUND key editable while env absent, then env wins and locks out writes', function () {
    e2e2Admin(['admin:write']);

    // Env absent → the key is editable; POST a DB value.
    Config::set(E2E2_ENVBOUND_KEY, '');
    e2e2Forget(E2E2_ENVBOUND_KEY);

    $this->postJson('/api/v2.1/admin/config/'.E2E2_ENVBOUND_KEY, [
        'value' => 'eu-west-9',
    ])
        ->assertOk()
        ->assertJsonPath('data.value', 'eu-west-9')
        ->assertJsonPath('data.source', 'db')
        ->assertJsonPath('data.locked', false);

    expect(ConfigCacheModel::where('k', E2E2_ENVBOUND_KEY)->value('v'))->toBe('eu-west-9');

    // GET confirms the db-sourced read.
    e2e2Admin(['admin:read']);
    $this->getJson('/api/v2.1/admin/config/'.E2E2_ENVBOUND_KEY)
        ->assertOk()
        ->assertJsonPath('data.value', 'eu-west-9')
        ->assertJsonPath('data.source', 'db')
        ->assertJsonPath('data.locked', false);

    // Now simulate boot resolution: env present + valid, config reflects env.
    e2e2SetProcessEnv(E2E2_ENVBOUND_VAR, 'us-east-1');
    Config::set(E2E2_ENVBOUND_KEY, 'us-east-1');
    e2e2Forget(E2E2_ENVBOUND_KEY);

    // Re-GET: env now wins the read and the key is locked.
    $this->getJson('/api/v2.1/admin/config/'.E2E2_ENVBOUND_KEY)
        ->assertOk()
        ->assertJsonPath('data.value', 'us-east-1')
        ->assertJsonPath('data.source', 'env')
        ->assertJsonPath('data.locked', true);

    // A write is now rejected and the stored DB value is untouched.
    e2e2Admin(['admin:write']);
    $this->postJson('/api/v2.1/admin/config/'.E2E2_ENVBOUND_KEY, [
        'value' => 'ap-south-1',
    ])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => [E2E2_ENVBOUND_KEY]]);

    expect(ConfigCacheModel::where('k', E2E2_ENVBOUND_KEY)->value('v'))->toBe('eu-west-9');
});

// Journey 3: a protected secret written over HTTP is masked on read, encrypted
// at rest, and resubmitting the masked placeholder is a no-op.
test('journey: protected secret is masked on read, encrypted at rest, masked re-POST is a no-op', function () {
    e2e2Admin(['admin:write']);

    // Env absent → the secret key is editable.
    Config::set(E2E2_PROTECTED_KEY, null);
    e2e2Forget(E2E2_PROTECTED_KEY);

    $secret = 'super-secret-hcaptcha-value';

    // POST the secret.
    $this->postJson('/api/v2.1/admin/config/'.E2E2_PROTECTED_KEY, [
        'value' => $secret,
    ])
        ->assertOk()
        ->assertJsonPath('data.protected', true);

    // GET single returns the MASKED value, never the plaintext.
    e2e2Admin(['admin:read']);
    $response = $this->getJson('/api/v2.1/admin/config/'.E2E2_PROTECTED_KEY)
        ->assertOk()
        ->assertJsonPath('data.protected', true);

    $masked = $response->json('data.value');
    expect($masked)->not->toBe($secret);
    expect($masked)->toContain('*');
    expect($masked)->toBe(ConfigCacheController::maskProtectedConfig($secret));

    // Raw DB column is ciphertext, not the plaintext; decrypt() recovers it.
    $rawDb = ConfigCacheModel::where('k', E2E2_PROTECTED_KEY)->value('v');
    expect($rawDb)->not->toBe($secret);
    expect(decrypt($rawDb))->toBe($secret);

    // POST the exact masked placeholder back → treated as 'no change'.
    e2e2Admin(['admin:write']);
    $this->postJson('/api/v2.1/admin/config/'.E2E2_PROTECTED_KEY, [
        'value' => $masked,
    ])
        ->assertOk()
        ->assertJsonPath('changed', []);

    // Stored secret unchanged.
    expect(decrypt(ConfigCacheModel::where('k', E2E2_PROTECTED_KEY)->value('v')))->toBe($secret);
});

// Journey 4: a single bulk POST mixing a valid key, an env-locked key, and an
// unknown key — only the valid write lands, the rest come back as errors.
test('journey: bulk partial success writes only the valid key and reports the rest as errors', function () {
    e2e2Admin(['admin:write']);

    // Valid ADMINONLY target.
    Config::set(E2E2_ADMINONLY_KEY, '/* default css */');
    e2e2Forget(E2E2_ADMINONLY_KEY);

    // Env-locked target (env present + valid).
    e2e2SetProcessEnv(E2E2_ENVBOUND_VAR, 'us-east-1');
    Config::set(E2E2_ENVBOUND_KEY, 'us-east-1');
    e2e2Forget(E2E2_ENVBOUND_KEY);

    $response = $this->postJson('/api/v2.1/admin/config', [
        'config' => [
            E2E2_ADMINONLY_KEY => '.ok { color: green; }',  // valid
            E2E2_ENVBOUND_KEY => 'eu-west-9',                // env-locked
            'this.key.is.not.cached' => 'nope',             // unknown
        ],
    ])
        ->assertOk()
        ->assertJsonStructure([
            'changed',
            'errors' => [E2E2_ENVBOUND_KEY, 'this.key.is.not.cached'],
        ]);

    // Only the valid key is reported under 'changed'.
    $changedKeys = collect($response->json('changed'))->pluck('key')->all();
    expect($changedKeys)->toBe([E2E2_ADMINONLY_KEY]);

    // DB reflects ONLY the valid write.
    expect(ConfigCacheModel::where('k', E2E2_ADMINONLY_KEY)->value('v'))->toBe('.ok { color: green; }');
    expect(ConfigCacheModel::where('k', E2E2_ENVBOUND_KEY)->exists())->toBeFalse();
    expect(ConfigCacheModel::where('k', 'this.key.is.not.cached')->exists())->toBeFalse();
});

// Journey 5: diagnostics reflects API-driven state, exercising sourceOf() end to
// end through the diagnostics path for both a db row and an env-locked key.
test('journey: diagnostics reflects API-written db value and env-locked key with correct source', function () {
    // Write an ADMINONLY value via the config POST endpoint.
    e2e2Admin(['admin:write']);
    Config::set(E2E2_ADMINONLY_KEY, '/* default css */');
    e2e2Forget(E2E2_ADMINONLY_KEY);
    $this->postJson('/api/v2.1/admin/config/'.E2E2_ADMINONLY_KEY, [
        'value' => '.diag { color: purple; }',
    ])->assertOk();

    // Also write the protected secret so we can assert its masked display.
    Config::set(E2E2_PROTECTED_KEY, null);
    e2e2Forget(E2E2_PROTECTED_KEY);
    $secret = 'diagnostics-secret-value';
    $this->postJson('/api/v2.1/admin/config/'.E2E2_PROTECTED_KEY, [
        'value' => $secret,
    ])->assertOk();

    // Leave an env key env-locked (env present + valid).
    e2e2SetProcessEnv(E2E2_ENVCONFIG_VAR, 'turnstile');
    Config::set(E2E2_ENVCONFIG_KEY, 'turnstile');
    e2e2Forget(E2E2_ENVCONFIG_KEY);

    // GET diagnostics as a read admin.
    e2e2Admin(['admin:read']);
    $response = $this->getJson('/api/v2.1/admin/diagnostics/config-cache')
        ->assertOk()
        ->assertJsonStructure([
            'rows' => [['key', 'source', 'list', 'locked', 'protected', 'effective']],
            'sync' => ['sync_hash', 'lock_held'],
        ]);

    $rows = collect($response->json('rows'))->keyBy('key');

    // The API-written ADMINONLY row reports source 'db' and its value.
    expect($rows[E2E2_ADMINONLY_KEY]['source'])->toBe('db');
    expect($rows[E2E2_ADMINONLY_KEY]['effective'])->toBe('.diag { color: purple; }');

    // The env-locked key reports source 'env' and the env value.
    expect($rows[E2E2_ENVCONFIG_KEY]['source'])->toBe('env');
    expect($rows[E2E2_ENVCONFIG_KEY]['locked'])->toBeTrue();
    expect($rows[E2E2_ENVCONFIG_KEY]['effective'])->toBe('turnstile');

    // The protected row is masked, never the plaintext.
    expect($rows[E2E2_PROTECTED_KEY]['protected'])->toBeTrue();
    expect($rows[E2E2_PROTECTED_KEY]['effective'])->not->toBe($secret);
    expect($rows[E2E2_PROTECTED_KEY]['effective'])->toContain('*');

    // Sync-health fields are present (lock_held observed as free during the request).
    expect($response->json('sync.sync_hash'))->toBeNull();
    expect($response->json('sync'))->toHaveKey('lock_held');
});

// Journey 6: a config write followed by clear-cache — the reconcile is selective:
// an ADMINONLY row survives while a drifted ENVBOUND row is corrected to env.
test('journey: clear-cache preserves ADMINONLY write but corrects a drifted ENVBOUND row', function () {
    e2e2Admin(['admin:write']);

    // Write an ADMINONLY value via the API.
    Config::set(E2E2_ADMINONLY_KEY, '/* default css */');
    e2e2Forget(E2E2_ADMINONLY_KEY);
    $this->postJson('/api/v2.1/admin/config/'.E2E2_ADMINONLY_KEY, [
        'value' => '.survives { color: orange; }',
    ])->assertOk();
    expect(ConfigCacheModel::where('k', E2E2_ADMINONLY_KEY)->value('v'))->toBe('.survives { color: orange; }');

    // An ENVBOUND key whose DB row has drifted away from the authoritative env value.
    e2e2SetProcessEnv(E2E2_ENVBOUND_VAR, 'us-east-1');
    Config::set(E2E2_ENVBOUND_KEY, 'us-east-1');
    ConfigCacheService::putRaw(E2E2_ENVBOUND_KEY, 'eu-west-9');
    e2e2Forget(E2E2_ENVBOUND_KEY);
    expect(ConfigCacheModel::where('k', E2E2_ENVBOUND_KEY)->value('v'))->toBe('eu-west-9');

    // Force a reconcile via the clear-cache endpoint.
    $this->postJson('/api/v2.1/admin/diagnostics/config-cache/clear-cache')
        ->assertOk()
        ->assertJsonPath('message', 'Config cache reconciled and cleared.');

    // ADMINONLY row survives (reconcile only governs ENVCONFIG keys).
    expect(ConfigCacheModel::where('k', E2E2_ADMINONLY_KEY)->value('v'))->toBe('.survives { color: orange; }');

    // The drifted ENVBOUND row is pulled back to the authoritative env value.
    expect(ConfigCacheModel::where('k', E2E2_ENVBOUND_KEY)->value('v'))->toBe('us-east-1');
});

// Journey 7: cross-endpoint scope enforcement — a read-scope admin can read but
// not write, a non-admin cannot write, and unauthenticated reads are 401.
test('journey: scopes enforced across endpoints (read-only cannot write, non-admin blocked, anon 401)', function () {
    // A read-scope admin: reads succeed, writes are 404 (scope enforced).
    e2e2Admin(['admin:read']);
    Config::set(E2E2_ADMINONLY_KEY, '/* sentinel default css */');
    e2e2Forget(E2E2_ADMINONLY_KEY);

    $this->getJson('/api/v2.1/admin/config/'.E2E2_ADMINONLY_KEY)->assertOk();

    $this->postJson('/api/v2.1/admin/config/'.E2E2_ADMINONLY_KEY, [
        'value' => '.blocked { color: red; }',
    ])->assertNotFound();
    $this->postJson('/api/v2.1/admin/config', [
        'config' => [E2E2_ADMINONLY_KEY => '.blocked { color: red; }'],
    ])->assertNotFound();

    // The rejected writes changed nothing: the value is still the default,
    // never either attempted write value.
    e2e2Forget(E2E2_ADMINONLY_KEY);
    expect(ConfigCacheService::get(E2E2_ADMINONLY_KEY))->toBe('/* sentinel default css */');

    // A non-admin holding admin:write is 404 on every write endpoint.
    $user = User::factory()->create(['is_admin' => false]);
    $user->refresh();
    Passport::actingAs($user, ['admin:write']);

    $this->postJson('/api/v2.1/admin/config/'.E2E2_ADMINONLY_KEY, [
        'value' => '.nope { color: red; }',
    ])->assertNotFound();
    $this->postJson('/api/v2.1/admin/config', [
        'config' => [E2E2_ADMINONLY_KEY => '.nope { color: red; }'],
    ])->assertNotFound();
    $this->postJson('/api/v2.1/admin/diagnostics/config-cache/clear-cache')->assertNotFound();

    e2e2Forget(E2E2_ADMINONLY_KEY);
    expect(ConfigCacheService::get(E2E2_ADMINONLY_KEY))->toBe('/* sentinel default css */');

    // Unauthenticated reads are 401.
    app('auth')->forgetGuards();
    $this->getJson('/api/v2.1/admin/config?keys[]='.E2E2_ADMINONLY_KEY)->assertUnauthorized();
    $this->getJson('/api/v2.1/admin/config/'.E2E2_ADMINONLY_KEY)->assertUnauthorized();
    $this->getJson('/api/v2.1/admin/diagnostics/config-cache')->assertUnauthorized();
});
