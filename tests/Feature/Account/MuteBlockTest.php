<?php

use App\Models\Profile;
use App\Models\User;
use App\Models\UserFilter;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Account Mute/Block Tests
|--------------------------------------------------------------------------
*/

describe('mute', function () {
    it('mutes another user', function () {
        $user = User::factory()->create();
        $user->refresh();
        $target = User::factory()->create();
        $target->refresh();

        $this->actingAs($user)
            ->postJson('/i/mute', [
                'type' => 'user',
                'item' => $target->profile_id,
            ])
            ->assertOk();

        expect(UserFilter::where('user_id', $user->profile_id)
            ->where('filterable_id', $target->profile_id)
            ->where('filter_type', 'mute')
            ->exists()
        )->toBeTrue();
    });

    it('prevents muting yourself', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->postJson('/i/mute', [
                'type' => 'user',
                'item' => $user->profile_id,
            ])
            ->assertForbidden();
    });

    it('validates required fields for mute', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->postJson('/i/mute', [])
            ->assertUnprocessable();
    });

    it('unmutes a previously muted user', function () {
        $user = User::factory()->create();
        $user->refresh();
        $target = User::factory()->create();
        $target->refresh();

        UserFilter::create([
            'user_id' => $user->profile_id,
            'filterable_id' => $target->profile_id,
            'filterable_type' => Profile::class,
            'filter_type' => 'mute',
        ]);

        $this->actingAs($user)
            ->postJson('/i/unmute', [
                'type' => 'user',
                'item' => $target->profile_id,
            ])
            ->assertOk();

        expect(UserFilter::where('user_id', $user->profile_id)
            ->where('filterable_id', $target->profile_id)
            ->where('filter_type', 'mute')
            ->exists()
        )->toBeFalse();
    });
});

describe('block', function () {
    it('blocks another user', function () {
        $user = User::factory()->create();
        $user->refresh();
        $target = User::factory()->create();
        $target->refresh();

        $this->actingAs($user)
            ->postJson('/i/block', [
                'type' => 'user',
                'item' => $target->profile_id,
            ])
            ->assertOk();

        expect(UserFilter::where('user_id', $user->profile_id)
            ->where('filterable_id', $target->profile_id)
            ->where('filter_type', 'block')
            ->exists()
        )->toBeTrue();
    });

    it('prevents blocking yourself', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->postJson('/i/block', [
                'type' => 'user',
                'item' => $user->profile_id,
            ])
            ->assertForbidden();
    });

    it('prevents blocking an admin', function () {
        $user = User::factory()->create();
        $user->refresh();
        $admin = User::factory()->admin()->create();
        $admin->refresh();

        $this->actingAs($user)
            ->postJson('/i/block', [
                'type' => 'user',
                'item' => $admin->profile_id,
            ])
            ->assertForbidden();
    });

    it('rejects invalid type parameter', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->postJson('/i/block', [
                'type' => 'invalid',
                'item' => 1,
            ])
            ->assertUnprocessable();
    });
});
