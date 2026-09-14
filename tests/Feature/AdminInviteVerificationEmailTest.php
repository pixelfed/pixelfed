<?php

use App\Mail\ConfirmEmail;
use App\Models\AdminInvite;
use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Admin-invite registration email verification
|--------------------------------------------------------------------------
|
| When an admin invite requires verification (skip_email_verification = false),
| apiRegister must send a verification email during the first session (mirroring
| RegisterController / the login flow), instead of leaving the user unverified
| with no email and only hitting the verification gate at next login. When the
| invite skips verification, the user is auto-verified and no email is sent.
|
*/

beforeEach(function () {
    config(['instance.admin_invites.enabled' => true]);
});

function makeInvite(bool $skipEmailVerification): AdminInvite
{
    $invite = new AdminInvite;
    $invite->skip_email_verification = $skipEmailVerification;
    $invite->max_uses = 10;
    $invite->uses = 0;
    $invite->save();

    return $invite->refresh();
}

it('sends a verification email when the invite requires verification', function () {
    Mail::fake();

    $invite = makeInvite(skipEmailVerification: false);

    $this->withoutMiddleware(ThrottleRequests::class)
        ->postJson('/api/v1.1/auth/invite/admin/re', [
            'token' => $invite->invite_code,
            'username' => 'inviteduser',
            'name' => 'Invited User',
            'email' => 'invited.admininvite@gmail.com',
            'password' => 'SecurePass123!',
            'password_confirm' => 'SecurePass123!',
        ])->assertRedirect('/');

    $user = User::where('email', 'invited.admininvite@gmail.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->email_verified_at)->toBeNull();

    // A verification row + email were produced for the new address.
    expect(
        EmailVerification::where('user_id', $user->id)->where('email', $user->email)->exists()
    )->toBeTrue();

    Mail::assertSent(ConfirmEmail::class);
});

it('auto-verifies and sends no email when the invite skips verification', function () {
    Mail::fake();

    $invite = makeInvite(skipEmailVerification: true);

    $this->withoutMiddleware(ThrottleRequests::class)
        ->postJson('/api/v1.1/auth/invite/admin/re', [
            'token' => $invite->invite_code,
            'username' => 'autoverified',
            'name' => 'Auto Verified',
            'email' => 'auto.admininvite@gmail.com',
            'password' => 'SecurePass123!',
            'password_confirm' => 'SecurePass123!',
        ])->assertRedirect('/');

    $user = User::where('email', 'auto.admininvite@gmail.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->email_verified_at)->not->toBeNull();

    expect(EmailVerification::where('user_id', $user->id)->exists())->toBeFalse();
    Mail::assertNothingSent();
});
