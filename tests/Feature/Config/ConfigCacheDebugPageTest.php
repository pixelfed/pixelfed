<?php

use App\Http\Controllers\Api\v2026\Admin\ConfigCacheDiagnostics;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Config Cache debug page (Task 17 / Task 14b)
|--------------------------------------------------------------------------
|
| The dedicated, read-only Config Cache debug page is a WEB route:
|   GET i/admin/diagnostics/config-cache  →  ConfigCache@debugPage
|   name 'admin.config-cache', middleware ['admin', 'dangerzone']
|
| 'dangerzone' is aliased to Laravel's RequirePassword (bootstrap/app.php), so
| a confirmed password is required — set via the session key
| 'auth.password_confirmed_at' => time(), exactly as AdminAccessTest and
| DirectoryPrivateProfileTest do for other admin/dangerzone web routes. The
| 'admin' middleware redirects non-admins to config('app.url').
|
| Validates: Requirements 12.1
|
*/

test('an admin with a confirmed password can open the config-cache debug page (12.1)', function () {
    $admin = User::factory()->admin()->create();
    $admin->refresh();

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('admin.config-cache'))
        ->assertOk()
        ->assertSee('Config Cache')
        ->assertSee('Sync Health');
});

test('a non-admin is blocked from the config-cache debug page', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $user->refresh();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('admin.config-cache'))
        ->assertRedirect(config('app.url'));
});

test('the config-cache debug route is registered with the admin + dangerzone middleware', function () {
    $route = collect(Route::getRoutes())
        ->first(fn ($r) => $r->getName() === 'admin.config-cache');

    expect($route)->not->toBeNull();
    expect($route->getActionName())
        ->toBe(ConfigCacheDiagnostics::class.'@debugPage');

    $middleware = $route->gatherMiddleware();
    expect($middleware)->toContain('admin');
    expect($middleware)->toContain('dangerzone');
});
