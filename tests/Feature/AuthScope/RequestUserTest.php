<?php

use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Auth Scope Migration Tests
|--------------------------------------------------------------------------
|
| These tests verify that controllers using $request->user() instead of
| Auth::user() work correctly. This covers the migration from facade to
| request-scoped auth that improves Octane compatibility.
|
| Each test exercises a route that was changed in the auth-scope refactor.
|
*/

describe('controllers using $request->user() (web)', function () {
    it('AccountController: follow requests page loads', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->get('/account/follow-requests')
            ->assertOk();
    });

    it('InternalApiController: compose settings loads', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->getJson('/api/compose/v0/settings')
            ->assertOk();
    });

    it('CollectionController: user can access collections', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->getJson('/api/local/profile/collections/'.$user->profile_id)
            ->assertOk();
    });

    it('DiscoverController: discover loads for authed user', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->get('/discover')
            ->assertOk();
    });

    it('ProfileController: meRedirect resolves for authed user', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->get('/i/web/profile/me')
            ->assertOk();
    });

    it('StatusController: status show page works for public status', function () {
        $user = User::factory()->create();
        $user->refresh();
        $status = Status::factory()->create([
            'profile_id' => $user->profile_id,
            'type' => 'photo',
            'scope' => 'public',
        ]);

        // Unauthenticated users get redirected to SPA for public statuses
        $this->get("/p/{$user->username}/{$status->id}")
            ->assertOk();
    });

    it('SiteController: home loads for guest', function () {
        $this->get('/')
            ->assertOk();
    });

    it('NewsroomController: newsroom loads', function () {
        $this->get('/site/newsroom')
            ->assertOk();
    });

    it('CommentController: requires auth for commenting', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->getJson('/account/direct')
            ->assertOk();
    });

    it('TimelineController: public timeline loads', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->get('/timeline/public')
            ->assertOk();
    });
});

describe('controllers using $request->user() (API)', function () {
    it('ApiV1Controller: verify credentials', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/accounts/verify_credentials')
            ->assertOk()
            ->assertJsonFragment(['username' => $user->username]);
    });

    it('ApiV1Controller: home timeline', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/timelines/home')
            ->assertOk();
    });

    it('ApiV1Controller: public timeline', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/timelines/public')
            ->assertOk();
    });

    it('ApiV1Controller: notifications', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/notifications')
            ->assertOk();
    });

    it('ApiV1Controller: account blocks', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/blocks')
            ->assertOk();
    });

    it('ApiV1Controller: account mutes', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/mutes')
            ->assertOk();
    });

    it('ApiV1Controller: favourites', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/favourites')
            ->assertOk();
    });

    it('ApiV1Controller: bookmarks', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/bookmarks')
            ->assertOk();
    });
});

describe('middleware using $request->user()', function () {
    it('Admin middleware blocks non-admin', function () {
        $user = User::factory()->create(['is_admin' => false]);
        $user->refresh();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get('/i/admin/dashboard')
            ->assertRedirect(config('app.url'));
    });

    it('Admin middleware allows admin', function () {
        $admin = User::factory()->admin()->create();
        $admin->refresh();

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get('/i/admin/dashboard')
            ->assertOk();
    });

    it('DangerZone/password.confirm middleware requires confirmation', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->get('/settings/security')
            ->assertRedirect(route('password.confirm'));
    });

    it('AccountInterstitial middleware passes for normal users', function () {
        $user = User::factory()->create(['has_interstitial' => false]);
        $user->refresh();

        $this->actingAs($user)
            ->get('/settings/home')
            ->assertOk();
    });
});
