<?php

use App\Follower;
use App\Profile;
use App\Status;
use App\User;
use App\UserFilter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Account Privacy Tests
|--------------------------------------------------------------------------
*/

describe('private profiles', function () {
    it('hides statuses from non-followers on private profile', function () {
        $user = User::factory()->create();
        $user->refresh();
        $private = User::factory()->create();
        $private->refresh();
        $private->profile->update(['is_private' => true]);

        Status::factory()->create([
            'profile_id' => $private->profile_id,
            'type' => 'photo',
            'scope' => 'private',
            'visibility' => 'private',
        ]);

        Passport::actingAs($user, ['read']);

        $this->getJson("/api/v1/accounts/{$private->profile_id}/statuses")
            ->assertOk()
            ->assertJson([]);
    });

    it('shows statuses to followers of a private profile', function () {
        $user = User::factory()->create();
        $user->refresh();
        $private = User::factory()->create();
        $private->refresh();
        $private->profile->update(['is_private' => true]);

        Status::factory()->create([
            'profile_id' => $private->profile_id,
            'type' => 'photo',
            'scope' => 'private',
            'visibility' => 'private',
        ]);

        Follower::create([
            'profile_id' => $user->profile_id,
            'following_id' => $private->profile_id,
        ]);

        Passport::actingAs($user, ['read']);

        $this->getJson("/api/v1/accounts/{$private->profile_id}/statuses")
            ->assertOk();
    });
});

describe('blocked users', function () {
    it('returns empty when viewing a profile that blocked you', function () {
        $user = User::factory()->create();
        $user->refresh();
        $blocker = User::factory()->create();
        $blocker->refresh();

        Status::factory()->count(3)->create([
            'profile_id' => $blocker->profile_id,
            'type' => 'photo',
            'scope' => 'public',
        ]);

        // Blocker blocks the user
        UserFilter::create([
            'user_id' => $blocker->profile_id,
            'filterable_id' => $user->profile_id,
            'filterable_type' => Profile::class,
            'filter_type' => 'block',
        ]);

        Passport::actingAs($user, ['read']);

        $this->getJson("/api/v1/accounts/{$blocker->profile_id}/statuses")
            ->assertOk()
            ->assertJson([]);
    });
});

describe('privacy settings', function () {
    it('toggles account privacy to private', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->post('/settings/privacy', [
                'is_private' => 'on',
            ])
            ->assertRedirect();

        $user->profile->refresh();
        expect((bool) $user->profile->is_private)->toBeTrue();
    });

    it('toggles account privacy to public', function () {
        $user = User::factory()->create();
        $user->refresh();
        $user->profile->update(['is_private' => true]);

        $this->actingAs($user)
            ->post('/settings/privacy', [])
            ->assertRedirect();

        $user->profile->refresh();
        expect((bool) $user->profile->is_private)->toBeFalse();
    });
});
