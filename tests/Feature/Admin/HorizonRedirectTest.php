<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| /horizon base-path redirect
|--------------------------------------------------------------------------
|
| Laravel 13's Horizon no longer redirects its base path, so routes/web.php
| adds an admin-only redirect from /horizon to the dashboard. The destination
| must be ABSOLUTE (leading slash): a relative "horizon/dashboard" Location is
| resolved by the browser against a trailing-slash request path /horizon/ to
| /horizon/horizon/dashboard (a doubled path served as HTTP 200 by Horizon's
| SPA catch-all), silently missing the dashboard.
|
*/

it('redirects an admin from /horizon to an absolute /horizon/dashboard', function () {
    $admin = User::factory()->admin()->create();
    $admin->refresh();

    $response = $this->actingAs($admin)->get('/horizon');

    $response->assertRedirect('/horizon/dashboard');

    // The Location header must be absolute so a trailing-slash request cannot
    // resolve it into a doubled /horizon/horizon/dashboard path.
    expect($response->headers->get('Location'))->toEndWith('/horizon/dashboard');
    expect(parse_url($response->headers->get('Location'), PHP_URL_PATH))
        ->toBe('/horizon/dashboard');
});

it('redirects with an absolute Location for a trailing-slash /horizon/ request', function () {
    $admin = User::factory()->admin()->create();
    $admin->refresh();

    $response = $this->actingAs($admin)->get('/horizon/');

    // Regardless of the trailing slash, the redirect path is the absolute
    // dashboard path, never a relative reference that would double up.
    expect(parse_url($response->headers->get('Location'), PHP_URL_PATH))
        ->toBe('/horizon/dashboard');
});

it('does not allow a non-admin to use the /horizon redirect', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $user->refresh();

    // The admin middleware bounces non-admins to the app root, not the dashboard.
    $this->actingAs($user)
        ->get('/horizon')
        ->assertRedirect(config('app.url'));
});
