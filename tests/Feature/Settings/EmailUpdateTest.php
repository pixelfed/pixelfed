<?php

use App\Mail\ConfirmEmail;
use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

/**
 * Confirm a password for the dangerzone middleware guarding settings routes.
 */
function confirmedSession(): array
{
    return ['auth.password_confirmed_at' => time()];
}

/*
|--------------------------------------------------------------------------
| POST /settings/email
|--------------------------------------------------------------------------
|
| The email form is pre-filled with the user's current address, so submitting
| unchanged must be a no-op (the unique rule must ignore the user's own row),
| while a genuinely new email still persists and collisions with other
| accounts still fail.
|
*/

it('accepts an unchanged email submission as a no-op', function () {
    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post('/settings/email', ['email' => $user->email])
        ->assertRedirect('/settings/email')
        ->assertSessionHasNoErrors();
});

it('persists a genuinely new email', function () {
    $user = User::factory()->create();
    $user->refresh();

    $newEmail = 'brand.new.'.uniqid().'@example.com';

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post('/settings/email', ['email' => $newEmail])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->email)->toBe($newEmail);
});

it('rejects an email already used by another account', function () {
    $other = User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post('/settings/email', ['email' => 'taken@example.com'])
        ->assertSessionHasErrors('email');

    expect($user->fresh()->email)->not->toBe('taken@example.com');
});

/*
|--------------------------------------------------------------------------
| Email verification on change (enforce_email_verification=true)
|--------------------------------------------------------------------------
|
| Changing email nulls email_verified_at for non-admins. The removed per-request
| /i/verify-email gate used to let them resend verification; that route is gone,
| so the change itself must now dispatch a verification email and the settings
| page must expose an in-app resend path.
|
*/

it('dispatches a verification email when a non-admin changes email and verification is enforced', function () {
    config(['pixelfed.enforce_email_verification' => true]);
    Mail::fake();

    $user = User::factory()->create();
    $user->refresh();

    $newEmail = 'changed.'.uniqid().'@example.com';

    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->post('/settings/email', ['email' => $newEmail])
        ->assertSessionHasNoErrors();

    $fresh = $user->fresh();

    expect($fresh->email)->toBe($newEmail)
        ->and($fresh->email_verified_at)->toBeNull();

    expect(
        EmailVerification::where('user_id', $user->id)->where('email', $newEmail)->exists()
    )->toBeTrue();

    Mail::assertSent(ConfirmEmail::class);
});

it('auto-verifies an admin email change without dispatching verification mail', function () {
    config(['pixelfed.enforce_email_verification' => true]);
    Mail::fake();

    $user = User::factory()->admin()->create();
    $user->refresh();

    $newEmail = 'admin.changed.'.uniqid().'@example.com';

    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->post('/settings/email', ['email' => $newEmail])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->email_verified_at)->not->toBeNull();

    Mail::assertNothingSent();
});

it('does not touch verification state when enforcement is disabled', function () {
    config(['pixelfed.enforce_email_verification' => false]);
    Mail::fake();

    $user = User::factory()->create();
    $user->refresh();

    $newEmail = 'noenforce.'.uniqid().'@example.com';

    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->post('/settings/email', ['email' => $newEmail])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->email_verified_at)->not->toBeNull();

    Mail::assertNothingSent();
});

it('resends a verification email for an authenticated unverified user', function () {
    Mail::fake();

    $user = User::factory()->unverified()->create();
    $user->refresh();

    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->withoutMiddleware(ThrottleRequests::class)
        ->post(route('settings.email.resend'))
        ->assertRedirect('/settings/email')
        ->assertSessionHas('status');

    expect(
        EmailVerification::where('user_id', $user->id)->where('email', $user->email)->exists()
    )->toBeTrue();

    Mail::assertSent(ConfirmEmail::class);
});

it('does not resend verification for an already-verified user', function () {
    Mail::fake();

    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->withoutMiddleware(ThrottleRequests::class)
        ->post(route('settings.email.resend'))
        ->assertRedirect('/settings/email');

    Mail::assertNothingSent();
});

it('rejects a resend inside the service cooldown window with an error', function () {
    Mail::fake();

    $user = User::factory()->unverified()->create();
    $user->refresh();

    // Simulate a verification link that was just sent for the current address,
    // putting the account inside the resend cooldown window.
    $seed = new EmailVerification;
    $seed->user_id = $user->id;
    $seed->email = $user->email;
    $seed->user_token = (string) Str::uuid().'seed';
    $seed->random_token = Str::random(64);
    $seed->save();

    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->withoutMiddleware(ThrottleRequests::class)
        ->post(route('settings.email.resend'))
        ->assertRedirect('/settings/email')
        ->assertSessionHasErrors('email');

    Mail::assertNothingSent();
});
