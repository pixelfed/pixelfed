<?php

use App\Jobs\DeletePipeline\DeleteAccountPipeline;
use App\Listeners\AuthLogin;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserOidcMapping;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);

    config([
        'cache.default' => 'array',
        'cache.limiter' => 'array',
        'vinylhub.account_edge.enabled' => true,
        'vinylhub.account_edge.service_token' => 'test-service-token',
    ]);

    Queue::fake();
    Cache::forget('pf:passport:personal-access-client-id:users');

    DB::table('oauth_clients')->insert([
        'user_id' => null,
        'name' => 'VinylHub test personal client',
        'secret' => Str::random(40),
        'provider' => null,
        'redirect' => '',
        'personal_access_client' => true,
        'password_client' => false,
        'revoked' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

function edgeRequest($test, string $method, string $uri, array $payload = [])
{
    return $test->withHeader('X-VinylHub-Service-Token', 'test-service-token')->{$method}($uri, $payload);
}

it('rejects ordinary callers and accepts the confidential service caller', function () {
    $payload = [
        'external_subject' => 'oneid-subject-authorization',
        'technical_handle' => 'vhabc123',
        'display_seed' => 'VinylHub account',
    ];

    $this->postJson('/api/v1/internal/vinylhub/account-edge/provision', $payload)
        ->assertNotFound();

    edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/provision', $payload)
        ->assertOk()
        ->assertJsonPath('lifecycle', 'active');
});

it('provisions one owner identity and reuses it on repeat', function () {
    $payload = [
        'external_subject' => 'oneid-subject-repeat',
        'technical_handle' => 'vhrepeat123',
        'display_seed' => 'Repeat account',
    ];

    $first = edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/provision', $payload)
        ->assertOk();
    $firstData = $first->json();

    $second = edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/provision', [
        ...$payload,
        'display_seed' => 'Changed seed must not change identity',
    ])->assertOk();

    expect($second->json('user_id'))->toBe($firstData['user_id']);
    expect($second->json('profile_id'))->toBe($firstData['profile_id']);
    expect(UserOidcMapping::where('oidc_id', $payload['external_subject'])->count())->toBe(1);
    expect(User::where('username', $payload['technical_handle'])->count())->toBe(1);
    expect(Profile::where('username', $payload['technical_handle'])->count())->toBe(1);
    expect($firstData['credential']['scopes'])->toBe(['read', 'write', 'follow']);
    expect(collect($firstData['credential']['scopes'])->contains(fn ($scope) => str_starts_with($scope, 'admin:')))->toBeFalse();
});

it('derives an internal non-delivery email and ignores legacy caller input', function () {
    $payload = [
        'external_subject' => 'opaque-managed-subject',
        'technical_handle' => 'vhinternal123',
        'display_seed' => 'Private Source Name',
    ];

    $first = edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/provision', $payload)
        ->assertOk();
    $firstData = $first->json();
    $user = User::findOrFail($firstData['user_id']);

    $legacy = edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/provision', [
        ...$payload,
        'technical_email' => 'person@example.com',
    ])->assertOk();

    expect($user->email)->toBe('vhinternal123@community.invalid');
    expect(Validator::make(['email' => $user->email], [
        'email' => ['required', 'string', 'email:strict', 'max:255'],
    ])->passes())->toBeTrue();
    expect($user->email)->not->toBe('person@example.com');
    expect($user->email)->not->toContain('Private Source Name');
    expect($user->email)->not->toContain('opaque-managed-subject');
    expect($firstData)->not->toHaveKey('email');
    expect($legacy->json('user_id'))->toBe($user->id);
});

it('keeps native compatibility emails unique for distinct managed identities', function () {
    $first = edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/provision', [
        'external_subject' => 'opaque-managed-first',
        'technical_handle' => 'vhmanagedfirst',
    ])->assertOk();
    $second = edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/provision', [
        'external_subject' => 'opaque-managed-second',
        'technical_handle' => 'vhmanagedsecond',
    ])->assertOk();

    $firstUser = User::findOrFail($first->json('user_id'));
    $secondUser = User::findOrFail($second->json('user_id'));

    expect($firstUser->email)->toBe('vhmanagedfirst@community.invalid');
    expect($secondUser->email)->toBe('vhmanagedsecond@community.invalid');
    expect($firstUser->email)->not->toBe($secondUser->email);
    expect(User::whereIn('id', [$firstUser->id, $secondUser->id])->count())->toBe(2);
});

it('converges when another writer wins the mapping race', function () {
    $subject = 'oneid-subject-race';
    $injected = false;

    DB::listen(function (QueryExecuted $query) use (&$injected, $subject) {
        $sql = strtolower($query->sql);

        if ($injected || ! str_contains($sql, 'user_oidc_mappings') || ! str_contains($sql, 'select')) {
            return;
        }

        $injected = true;
        $user = User::factory()->create([
            'username' => 'vhracewinner',
            'email' => 'vhracewinner@community.invalid',
        ]);

        UserOidcMapping::create([
            'user_id' => $user->id,
            'oidc_id' => $subject,
        ]);
    });

    $response = edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/provision', [
        'external_subject' => $subject,
        'technical_handle' => 'vhracecaller',
    ])->assertOk();

    expect($injected)->toBeTrue();
    expect($response->json('technical_handle'))->toBe('vhracewinner');
    expect(UserOidcMapping::where('oidc_id', $subject)->count())->toBe(1);
    expect(User::where('username', 'vhracecaller')->count())->toBe(0);
});

it('does not use source PII for the technical owner identity', function () {
    $response = edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/provision', [
        'external_subject' => 'wechat-subject-opaque',
        'technical_handle' => 'vhopaque987',
        'display_seed' => 'Mutable nickname',
    ])->assertOk();

    $user = User::findOrFail($response->json('user_id'));

    expect($user->username)->toBe('vhopaque987');
    expect($user->email)->toBe('vhopaque987@community.invalid');
    expect($user->username)->not->toContain('wechat');
    expect($user->email)->not->toContain('wechat');
});

it('repairs a mapped user with a missing profile before any repeat create', function () {
    $user = User::factory()->create([
        'username' => 'vhrepair123',
        'email' => 'vhrepair123@community.invalid',
    ]);
    $user->profile()->forceDelete();
    $user->profile_id = null;
    $user->saveQuietly();
    UserOidcMapping::create([
        'user_id' => $user->id,
        'oidc_id' => 'oneid-subject-repair',
    ]);

    edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/read', [
        'external_subject' => 'oneid-subject-repair',
        'repair' => true,
    ])->assertOk()->assertJsonPath('lifecycle', 'active');

    edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/provision', [
        'external_subject' => 'oneid-subject-repair',
        'technical_handle' => 'vhrepair123',
        'display_seed' => 'Repair seed',
    ])->assertOk()->assertJsonPath('user_id', $user->id);

    expect(User::where('username', 'vhrepair123')->count())->toBe(1);
    expect(Profile::where('username', 'vhrepair123')->count())->toBe(1);
});

it('reports a missing mapping without creating an identity', function () {
    edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/read', [
        'external_subject' => 'oneid-subject-missing',
    ])->assertOk()
        ->assertJsonPath('projection_exists', false)
        ->assertJsonPath('lifecycle', 'missing');

    expect(User::count())->toBe(0);
    expect(UserOidcMapping::count())->toBe(0);
});

it('supports credential renewal and revocation without admin scopes', function () {
    $payload = [
        'external_subject' => 'oneid-subject-token',
        'technical_handle' => 'vhtoken123',
    ];

    $first = edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/provision', $payload)->assertOk();
    $oldTokenId = $first->json('credential.id');

    $renewed = edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/credential/renew', [
        'external_subject' => $payload['external_subject'],
    ])->assertOk();

    expect($renewed->json('credential.access_token'))->not->toBeNull();
    expect($renewed->json('credential.scopes'))->toBe(['read', 'write', 'follow']);
    expect($renewed->json('renewed_token_id'))->toBe($oldTokenId);

    edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/credential/revoke', [
        'external_subject' => $payload['external_subject'],
    ])->assertOk()->assertJsonPath('credential.status', 'revoked');
});

it('suspends, resumes, and deletes by stable service mapping', function () {
    $payload = [
        'external_subject' => 'oneid-subject-lifecycle',
        'technical_handle' => 'vhlifecycle123',
    ];

    edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/provision', $payload)->assertOk();

    edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/suspend', [
        'external_subject' => $payload['external_subject'],
    ])->assertOk()->assertJsonPath('lifecycle', 'suspended');

    edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/resume', [
        'external_subject' => $payload['external_subject'],
    ])->assertOk()->assertJsonPath('lifecycle', 'active');

    edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/delete', [
        'external_subject' => $payload['external_subject'],
    ])->assertOk()->assertJsonPath('lifecycle', 'delete_requested');

    edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/delete-status', [
        'external_subject' => $payload['external_subject'],
    ])->assertOk()->assertJsonPath('lifecycle', 'delete_requested');
});

it('retains terminal delete readback after native deletion and session removal', function () {
    $subject = 'oneid-subject-terminal-delete';
    $payload = [
        'external_subject' => $subject,
        'technical_handle' => 'vhterminaldelete',
    ];

    $provision = edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/provision', $payload)->assertOk();
    $user = User::findOrFail($provision->json('user_id'));

    $this->actingAs($user);
    expect(auth()->check())->toBeTrue();

    edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/delete', [
        'external_subject' => $subject,
    ])->assertOk()->assertJsonPath('lifecycle', 'delete_requested');

    auth()->logout();
    expect(auth()->check())->toBeFalse();

    Redis::shouldReceive('zcard')->andReturn(0);
    Redis::shouldReceive('del')->andReturn(1);
    (new DeleteAccountPipeline($user->fresh()))->handle();

    $user->refresh();
    $profile = Profile::withTrashed()->whereUserId($user->id)->firstOrFail();

    expect($user->status)->toBe('deleted');
    expect($profile->deleted_at)->not->toBeNull();
    expect(UserOidcMapping::where('oidc_id', $subject)->count())->toBe(1);

    edgeRequest($this, 'postJson', '/api/v1/internal/vinylhub/account-edge/delete-status', [
        'external_subject' => $subject,
    ])->assertOk()
        ->assertJsonPath('lifecycle', 'deleted')
        ->assertJsonPath('projection_exists', true)
        ->assertJsonPath('repair_required', false);
});

it('uses the shared initializer for ordinary login repair', function () {
    $user = User::factory()->create();
    $user->profile()->forceDelete();
    $user->settings()->delete();
    $user->profile_id = null;
    $user->saveQuietly();

    app(AuthLogin::class)->handle((object) ['user' => $user]);

    $user->refresh();
    expect($user->profile_id)->not->toBeNull();
    expect($user->profile()->first()->private_key)->not->toBeNull();
    expect($user->profile()->first()->public_key)->not->toBeNull();
    expect($user->settings()->exists())->toBeTrue();
});
