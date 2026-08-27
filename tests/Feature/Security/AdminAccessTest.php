<?php

use App\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Admin Access Security Tests
|--------------------------------------------------------------------------
|
| These tests verify that non-admin users cannot access any admin
| functionality through web or API routes. This is critical security
| coverage that must pass before any deployment.
|
*/

describe('web admin routes deny non-admin users', function () {
    it('blocks non-admin from admin dashboard', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $user->refresh();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get('/i/admin/dashboard')
            ->assertRedirect(config('app.url'));
    });

    it('blocks non-admin from admin users page', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $user->refresh();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get('/i/admin/users/show/1')
            ->assertRedirect(config('app.url'));
    });

    it('blocks non-admin from admin reports', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $user->refresh();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get('/i/admin/reports')
            ->assertRedirect(config('app.url'));
    });

    it('blocks non-admin from admin settings', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $user->refresh();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get('/i/admin/settings')
            ->assertRedirect(config('app.url'));
    });

    it('blocks non-admin from admin instances', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $user->refresh();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get('/i/admin/instances')
            ->assertRedirect(config('app.url'));
    });

    it('blocks non-admin from curated onboarding', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $user->refresh();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get('/i/admin/curated-onboarding/home')
            ->assertRedirect(config('app.url'));
    });
});

describe('API admin routes deny non-admin users', function () {
    it('blocks non-admin from admin stats API', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $user->refresh();
        Passport::actingAs($user, ['admin:read']);

        $this->getJson('/api/admin/stats')
            ->assertNotFound();
    });

    it('blocks non-admin from admin users list API', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $user->refresh();
        Passport::actingAs($user, ['admin:read']);

        $this->getJson('/api/admin/users/list')
            ->assertNotFound();
    });

    it('blocks non-admin from admin config API', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $user->refresh();
        Passport::actingAs($user, ['admin:read']);

        $this->getJson('/api/admin/config')
            ->assertNotFound();
    });

    it('blocks non-admin from admin instances API', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $user->refresh();
        Passport::actingAs($user, ['admin:read']);

        $this->getJson('/api/admin/instances/list')
            ->assertNotFound();
    });

    it('blocks non-admin from admin reports API', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $user->refresh();
        Passport::actingAs($user, ['admin:read']);

        $this->getJson('/api/admin/mod-reports/list')
            ->assertNotFound();
    });

    it('blocks non-admin from v1 admin domain blocks', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $user->refresh();
        Passport::actingAs($user, ['admin:read']);

        $this->getJson('/api/v1/admin/domain_blocks')
            ->assertForbidden();
    });

    it('blocks non-admin from internal admin API', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $user->refresh();

        $this->actingAs($user)
            ->getJson('/i/admin/api/stats')
            ->assertRedirect(config('app.url'));
    });

    it('blocks non-admin from admin settings fetch', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $user->refresh();

        $this->actingAs($user)
            ->getJson('/i/admin/api/settings/fetch')
            ->assertRedirect(config('app.url'));
    });
});

describe('admin write operations deny non-admin users', function () {
    it('blocks non-admin from admin user actions', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $user->refresh();
        Passport::actingAs($user, ['admin:write']);

        $this->postJson('/api/v1/admin/domain_blocks', [
            'domain' => 'evil.example.com',
        ])->assertForbidden();
    });

    it('blocks non-admin from deleting domain blocks', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $user->refresh();
        Passport::actingAs($user, ['admin:write']);

        $this->deleteJson('/api/v1/admin/domain_blocks/1')
            ->assertForbidden();
    });
});

describe('unauthenticated admin access', function () {
    it('redirects unauthenticated users from admin dashboard', function () {
        $response = $this->get('/i/admin/dashboard');
        // Should redirect to login (may go via password confirm)
        $response->assertRedirect();
    });

    it('returns 401 for unauthenticated admin API requests', function () {
        $this->getJson('/api/admin/stats')
            ->assertUnauthorized();
    });

    it('returns 401 for unauthenticated v1 admin API', function () {
        $this->getJson('/api/v1/admin/domain_blocks')
            ->assertUnauthorized();
    });
});

describe('admin access granted to admin users', function () {
    it('allows admin to access dashboard', function () {
        $admin = User::factory()->admin()->create();
        $admin->refresh();

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get('/i/admin/dashboard')
            ->assertOk();
    });

    it('allows admin to access admin stats API', function () {
        $admin = User::factory()->admin()->create();
        $admin->refresh();
        Passport::actingAs($admin, ['admin:read']);

        $this->getJson('/api/admin/stats')
            ->assertOk();
    });

    it('allows admin to access admin users API', function () {
        $admin = User::factory()->admin()->create();
        $admin->refresh();
        Passport::actingAs($admin, ['admin:read']);

        $this->getJson('/api/admin/users/list')
            ->assertOk();
    });

    it('allows admin to access v1 admin domain blocks', function () {
        $admin = User::factory()->admin()->create();
        $admin->refresh();
        Passport::actingAs($admin, ['admin:read']);

        $this->getJson('/api/v1/admin/domain_blocks')
            ->assertOk();
    });
});
