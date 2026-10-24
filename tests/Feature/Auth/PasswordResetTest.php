<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Password Reset
|--------------------------------------------------------------------------
*/

it('renders the password reset request page', function () {
    $this->get('/password/reset')
        ->assertOk();
});

it('sends a password reset link to a valid email', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/password/email', [
        'email' => $user->email,
    ])->assertRedirect();

    Notification::assertSentTo($user, ResetPassword::class);
});

it('does not reveal whether an email exists', function () {
    Notification::fake();

    $this->post('/password/email', [
        'email' => 'nonexistent@example.com',
    ])->assertRedirect();

    Notification::assertNothingSent();
});

it('renders the password reset form with a valid token', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->get("/password/reset/{$token}")
        ->assertOk();
});

it('resets the password with a valid token', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->post('/password/reset', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewSecurePass123!',
        'password_confirmation' => 'NewSecurePass123!',
    ])->assertRedirect();

    $user->refresh();
    expect(Hash::check('NewSecurePass123!', $user->password))->toBeTrue();
});

it('rejects password reset with an invalid token', function () {
    $user = User::factory()->create([
        'password' => Hash::make('original-password'),
    ]);

    $this->post('/password/reset', [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'NewSecurePass123!',
        'password_confirmation' => 'NewSecurePass123!',
    ])->assertRedirect();

    $user->refresh();
    expect(Hash::check('original-password', $user->password))->toBeTrue();
});
