<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Temporary account disable must revoke OAuth tokens
|--------------------------------------------------------------------------
|
| removeAccountTemporarySubmit flipped status to disabled and logged out the
| web session, but left oauth_access_tokens / oauth_refresh_tokens intact, so
| a previously-authorized third-party client kept a live bearer token. Both
| must be revoked (refresh via access_token_id, which is its only FK).
|
*/

beforeEach(function () {
    config(['pixelfed.account_deletion' => true]);
});

function seedOauthTokens(User $user): array
{
    $clientId = DB::table('oauth_clients')->insertGetId([
        'user_id' => $user->id,
        'name' => 'Test App',
        'secret' => Str::random(40),
        'redirect' => 'https://example.test/cb',
        'personal_access_client' => false,
        'password_client' => false,
        'revoked' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $atId = (string) Str::uuid();
    DB::table('oauth_access_tokens')->insert([
        'id' => $atId,
        'user_id' => $user->id,
        'client_id' => $clientId,
        'name' => 'token',
        'scopes' => json_encode(['read', 'write']),
        'revoked' => false,
        'created_at' => now(),
        'updated_at' => now(),
        'expires_at' => now()->addDays(365),
    ]);

    $rtId = (string) Str::uuid();
    DB::table('oauth_refresh_tokens')->insert([
        'id' => $rtId,
        'access_token_id' => $atId,
        'revoked' => false,
        'expires_at' => now()->addDays(400),
    ]);

    return [$atId, $rtId];
}

it('revokes oauth access and refresh tokens when temporarily disabling', function () {
    $user = User::factory()->create();
    $user->refresh();

    [$atId, $rtId] = seedOauthTokens($user);

    expect(DB::table('oauth_access_tokens')->where('id', $atId)->exists())->toBeTrue();
    expect(DB::table('oauth_refresh_tokens')->where('id', $rtId)->exists())->toBeTrue();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post('/settings/remove/request/temporary')
        ->assertRedirect('/');

    // Account is disabled.
    expect($user->fresh()->status)->toBe('disabled');

    // Both token rows must be gone, so the third-party bearer/refresh is dead.
    expect(DB::table('oauth_access_tokens')->where('id', $atId)->exists())->toBeFalse();
    expect(DB::table('oauth_refresh_tokens')->where('id', $rtId)->exists())->toBeFalse();
});
