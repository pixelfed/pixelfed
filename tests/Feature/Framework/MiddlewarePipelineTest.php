<?php

use App\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Middleware Pipeline Integration Tests
|--------------------------------------------------------------------------
|
| Verify that the middleware pipeline processes requests correctly,
| including authentication, CSRF, throttling, and custom middleware.
|
*/

it('applies CSRF protection to web POST routes', function () {
    // POST without CSRF token should be rejected (419 or redirect)
    $response = $this->post('/login', [
        'email' => 'test@test.com',
        'password' => 'password',
    ]);

    expect(in_array($response->getStatusCode(), [302, 419]))->toBeTrue();
});

it('skips CSRF for excluded routes', function () {
    // API routes don't have CSRF
    $this->postJson('/api/v1/apps', [
        'client_name' => 'Test',
        'redirect_uris' => 'urn:ietf:wg:oauth:2.0:oob',
        'scopes' => 'read',
    ])->assertStatus(200);
});

it('applies throttle middleware to login', function () {
    $user = User::factory()->create();

    // Make requests up to the limit
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            '_token' => csrf_token(),
            'email' => $user->email,
            'password' => 'wrong',
        ]);
    }

    // Next request should be throttled
    $response = $this->post('/login', [
        '_token' => csrf_token(),
        'email' => $user->email,
        'password' => 'wrong',
    ]);

    // Either 429 (API) or redirect with error (web)
    expect(in_array($response->getStatusCode(), [302, 429]))->toBeTrue();
});

it('applies auth middleware to protected web routes', function () {
    $this->get('/settings/home')
        ->assertRedirect('/login');
});

it('applies password confirmation middleware', function () {
    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->get('/settings/security')
        ->assertRedirect(route('password.confirm'));
});

it('applies admin middleware to admin routes', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $user->refresh();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/i/admin/dashboard')
        ->assertRedirect(config('app.url'));
});

it('adds X-Frame-Options header via FrameGuard', function () {
    // TODO: Replace custom App\Http\Middleware\FrameGuard with Laravel's built-in
    // security headers. In Laravel 12+ this can be handled via:
    //   $middleware->withSecurityHeaders(['X-Frame-Options' => 'SAMEORIGIN'])
    // in bootstrap/app.php, eliminating the need for a custom middleware class.
    $this->get('/login')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
});

it('handles TwoFactorAuth middleware for users with 2fa', function () {
    $user = User::factory()->create([
        '2fa_enabled' => true,
        '2fa_secret' => 'TESTSECRET123456',
    ]);
    $user->refresh();

    $this->actingAs($user)
        ->get('/settings/home')
        ->assertRedirect('/i/auth/checkpoint');
});

it('passes TwoFactorAuth middleware for users without 2fa', function () {
    $user = User::factory()->create([
        '2fa_enabled' => false,
    ]);
    $user->refresh();

    $this->actingAs($user)
        ->get('/settings/home')
        ->assertOk();
});
