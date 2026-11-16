<?php

use App\Models\AdminInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['instance.admin_invites.enabled' => true]);
    $this->withoutMiddleware(ThrottleRequests::class);
});

function adminInviteForTokenTest(): AdminInvite
{
    $invite = new AdminInvite;
    $invite->skip_email_verification = true;
    $invite->max_uses = 1;
    $invite->uses = 0;
    $invite->save();

    return $invite->refresh();
}

it('accepts the real invite code', function () {
    $invite = adminInviteForTokenTest();

    $this->postJson('/api/v1.1/auth/invite/admin/verify', ['token' => $invite->invite_code])
        ->assertOk()
        ->assertJsonPath('max_uses', 1);
});

it('rejects invite tokens that are not strings', function (string $endpoint, array $extra, mixed $token) {
    adminInviteForTokenTest();

    $this->postJson($endpoint, ['token' => $token] + $extra)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['token']);
})->with([
    'verify' => ['/api/v1.1/auth/invite/admin/verify', []],
    'username check' => ['/api/v1.1/auth/invite/admin/uc', ['username' => 'mallory']],
    'email check' => ['/api/v1.1/auth/invite/admin/ec', ['email' => 'mallory@example.org']],
])->with([
    'integer zero' => 0,
    'integer' => 3,
    'boolean' => true,
    'array' => [['a']],
]);

it('does not register with a non-string invite token', function () {
    $invite = adminInviteForTokenTest();

    $this->postJson('/api/v1.1/auth/invite/admin/re', [
        'token' => 0,
        'username' => 'mallory',
        'name' => 'Mallory',
        'email' => 'mallory@example.org',
        'password' => 'Long-Test-Passw0rd!!',
        'password_confirm' => 'Long-Test-Passw0rd!!',
    ])->assertStatus(422)->assertJsonValidationErrors(['token']);

    expect(User::whereUsername('mallory')->exists())->toBeFalse();
    expect($invite->fresh()->uses)->toBe(0);
});

it('requires the exact invite code', function () {
    $invite = adminInviteForTokenTest();

    $this->postJson('/api/v1.1/auth/invite/admin/verify', ['token' => strtoupper($invite->invite_code)])
        ->assertNotFound();

    $this->postJson('/api/v1.1/auth/invite/admin/verify', ['token' => substr($invite->invite_code, 0, 8)])
        ->assertNotFound();
});
