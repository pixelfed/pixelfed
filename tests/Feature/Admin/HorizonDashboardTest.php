<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Horizon base-path dashboard
|--------------------------------------------------------------------------
|
| Horizon lives under `admin/horizon` (config('horizon.path')) so its routes can
| never collide with the `{username}` profile catch-all. Horizon's own optional
| catch-all `GET {view?}` serves the base path directly by rendering the dashboard
| SPA, so there is no separate base-path redirect. Access is gated by Horizon's
| own auth middleware + the `viewHorizon` gate (admins only).
|
*/

it('serves the Horizon dashboard at the base path for an admin', function () {
    $path = config('horizon.path');

    $admin = User::factory()->admin()->create();
    $admin->refresh();

    // The base path is handled by Horizon (not a redirect) and renders the SPA.
    $this->actingAs($admin)
        ->get('/'.$path)
        ->assertOk()
        ->assertSee('Horizon', false);
});

it('serves the Horizon dashboard at the /dashboard subpath for an admin', function () {
    $path = config('horizon.path');

    $admin = User::factory()->admin()->create();
    $admin->refresh();

    $this->actingAs($admin)
        ->get('/'.$path.'/dashboard')
        ->assertOk();
});

it('does not allow a non-admin to access the Horizon dashboard', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $user->refresh();

    // Horizon's authorization gate (viewHorizon) forbids non-admins.
    $this->actingAs($user)
        ->get('/'.config('horizon.path'))
        ->assertForbidden();
});
