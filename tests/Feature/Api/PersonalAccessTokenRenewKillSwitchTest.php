<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Passport\ClientRepository;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| PAT renew kill-switch
|--------------------------------------------------------------------------
|
| renew() mints a brand-new PAT, so it must honor the same
| instance.oauth.pat_enabled kill-switch that store() enforces. Otherwise a
| user holding an existing PAT can keep minting fresh tokens after an admin
| has disabled the feature.
|
*/

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        'Test Personal Access Client',
        config('auth.guards.api.provider')
    );
});

it('refuses to renew a PAT when the feature is disabled', function () {
    $user = User::factory()->create();
    $user->refresh();
    $this->actingAs($user, 'web');

    // Create a real PAT while the feature is enabled.
    config(['instance.oauth.pat_enabled' => true]);
    $create = $this->postJson('/oauth/personal-access-tokens', [
        'name' => 'my PAT',
        'scopes' => ['read'],
    ])->assertOk();
    $oldId = $create->json('token.id');

    // Operator disables the PAT feature.
    config(['instance.oauth.pat_enabled' => false]);

    // store() correctly refuses.
    $this->postJson('/oauth/personal-access-tokens', [
        'name' => 'second',
        'scopes' => ['read'],
    ])->assertStatus(403);

    // renew() must also refuse, and must NOT revoke the old token.
    $this->postJson("/oauth/personal-access-tokens/{$oldId}/renew")
        ->assertStatus(403);

    $this->assertDatabaseHas('oauth_access_tokens', ['id' => $oldId, 'revoked' => 0]);
});

it('allows renew when the feature is enabled', function () {
    $user = User::factory()->create();
    $user->refresh();
    $this->actingAs($user, 'web');

    config(['instance.oauth.pat_enabled' => true]);
    $create = $this->postJson('/oauth/personal-access-tokens', [
        'name' => 'my PAT',
        'scopes' => ['read'],
    ])->assertOk();
    $oldId = $create->json('token.id');

    $renew = $this->postJson("/oauth/personal-access-tokens/{$oldId}/renew")
        ->assertOk();

    expect($renew->json('accessToken'))->not->toBeEmpty()
        ->and($renew->json('token.id'))->not->toBe($oldId)
        ->and($renew->json('renewedTokenId'))->toBe($oldId);

    $this->assertDatabaseHas('oauth_access_tokens', ['id' => $oldId, 'revoked' => 1]);
});
