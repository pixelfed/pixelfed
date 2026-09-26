<?php

use App\Models\AccountLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use PragmaRX\Google2FA\Google2FA;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Removing 2FA must require proof of the second factor
|--------------------------------------------------------------------------
|
| securityTwoFactorUpdate previously disabled 2FA on password/sudo alone, so
| anyone with an authenticated session could permanently remove 2FA without
| the device. Removal now requires a current TOTP code or an unused backup
| code, and writes an audit log entry.
|
*/

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    config(['instance.enable_cc' => false]);
});

function twoFactorUser(array $backupCodes = ['BACKUPCODEONE12345678901']): User
{
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();

    $user = User::factory()->create([
        '2fa_enabled' => true,
        '2fa_secret' => $secret,
        '2fa_backup_codes' => json_encode($backupCodes),
        '2fa_setup_at' => now(),
    ]);
    $user->refresh();

    return $user;
}

function sudoSession(): array
{
    return ['auth.password_confirmed_at' => time()];
}

it('rejects removal when no code is supplied', function () {
    $user = twoFactorUser();

    $this->actingAs($user)
        ->withSession(sudoSession())
        ->postJson('/settings/security/2fa/edit', ['action' => 'remove'])
        ->assertStatus(422);

    $fresh = $user->fresh();
    expect((bool) $fresh->{'2fa_enabled'})->toBeTrue();
    expect($fresh->{'2fa_secret'})->not->toBeNull();
});

it('rejects removal with an invalid code', function () {
    $user = twoFactorUser();

    $this->actingAs($user)
        ->withSession(sudoSession())
        ->postJson('/settings/security/2fa/edit', ['action' => 'remove', 'code' => '000000'])
        ->assertStatus(403);

    $fresh = $user->fresh();
    expect((bool) $fresh->{'2fa_enabled'})->toBeTrue();
    expect($fresh->{'2fa_secret'})->not->toBeNull();
});

it('removes 2FA with a valid TOTP code and writes an audit log', function () {
    $user = twoFactorUser();
    $code = (new Google2FA)->getCurrentOtp($user->{'2fa_secret'});

    $this->actingAs($user)
        ->withSession(sudoSession())
        ->postJson('/settings/security/2fa/edit', ['action' => 'remove', 'code' => $code])
        ->assertOk()
        ->assertJson(['msg' => 'Successfully removed 2fa device']);

    $fresh = $user->fresh();
    expect((bool) $fresh->{'2fa_enabled'})->toBeFalse();
    expect($fresh->{'2fa_secret'})->toBeNull();
    expect($fresh->{'2fa_backup_codes'})->toBeNull();
    expect($fresh->{'2fa_setup_at'})->toBeNull();

    expect(AccountLog::whereUserId($user->id)->whereAction('account.security.2fa.remove')->exists())->toBeTrue();
});

it('removes 2FA with a valid backup code', function () {
    $backup = 'BACKUPCODEONE12345678901';
    $user = twoFactorUser([$backup, 'SECONDBACKUPCODE12345678']);

    $this->actingAs($user)
        ->withSession(sudoSession())
        ->postJson('/settings/security/2fa/edit', ['action' => 'remove', 'code' => $backup])
        ->assertOk()
        ->assertJson(['msg' => 'Successfully removed 2fa device']);

    $fresh = $user->fresh();
    expect((bool) $fresh->{'2fa_enabled'})->toBeFalse();
    expect($fresh->{'2fa_secret'})->toBeNull();
});

it('rejects a non-remove action', function () {
    $user = twoFactorUser();
    $code = (new Google2FA)->getCurrentOtp($user->{'2fa_secret'});

    $this->actingAs($user)
        ->withSession(sudoSession())
        ->postJson('/settings/security/2fa/edit', ['action' => 'disable', 'code' => $code])
        ->assertStatus(403);

    expect((bool) $user->fresh()->{'2fa_enabled'})->toBeTrue();
});
