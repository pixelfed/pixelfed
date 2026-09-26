<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use PragmaRX\Google2FA\Google2FA;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| 2FA setup must not expose backup codes before verification
|--------------------------------------------------------------------------
|
| The setup GET page generated, persisted, and rendered backup codes into the
| HTML before the user proved possession of the authenticator, and regenerated
| them on every refresh (invalidating copied codes). Backup codes are now
| created and returned only after the TOTP code verifies, and are not present
| in the setup page source.
|
*/

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    config(['instance.enable_cc' => false]);
});

function sudo(): array
{
    return ['auth.password_confirmed_at' => time()];
}

it('does not render or persist backup codes on the setup GET page', function () {
    $user = User::factory()->create(['2fa_enabled' => false]);
    $user->refresh();

    $response = $this->actingAs($user)
        ->withSession(sudo())
        ->get('/settings/security/2fa/setup')
        ->assertOk();

    // A secret is provisioned (needed to add the authenticator)...
    $user->refresh();
    expect($user->{'2fa_secret'})->not->toBeNull();

    // ...but backup codes are NOT generated/persisted on GET...
    expect($user->{'2fa_backup_codes'})->toBeNull();

    // ...and the backup-codes container renders empty (no codes injected
    // server-side). Since none are generated on GET, none can leak.
    expect($response->getContent())->toContain('<code id="backup-codes"></code>');
});

it('generates backup codes only after a valid code is verified', function () {
    $user = User::factory()->create(['2fa_enabled' => false]);
    $user->refresh();

    // Establish a secret (as the GET page would).
    $secret = (new Google2FA)->generateSecretKey(32);
    $user->{'2fa_secret'} = $secret;
    $user->save();

    $code = (new Google2FA)->getCurrentOtp($secret);

    $json = $this->actingAs($user)
        ->withSession(sudo())
        ->postJson('/settings/security/2fa/setup', ['code' => $code])
        ->assertOk()
        ->assertJson(['msg' => 'success'])
        ->json();

    // Codes returned once in the response...
    expect($json['backup_codes'] ?? null)->toBeArray();
    expect(count($json['backup_codes']))->toBeGreaterThan(0);

    // ...and now persisted + 2FA enabled.
    $user->refresh();
    expect((bool) $user->{'2fa_enabled'})->toBeTrue();
    expect($user->{'2fa_backup_codes'})->not->toBeNull();
    expect(json_decode($user->{'2fa_backup_codes'}, true))->toBe($json['backup_codes']);
});

it('does not generate backup codes on an invalid verification', function () {
    $user = User::factory()->create(['2fa_enabled' => false]);
    $user->refresh();

    $user->{'2fa_secret'} = (new Google2FA)->generateSecretKey(32);
    $user->save();

    $this->actingAs($user)
        ->withSession(sudo())
        ->postJson('/settings/security/2fa/setup', ['code' => '000000'])
        ->assertStatus(403);

    $user->refresh();
    expect((bool) $user->{'2fa_enabled'})->toBeFalse();
    expect($user->{'2fa_backup_codes'})->toBeNull();
});
