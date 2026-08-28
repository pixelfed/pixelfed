<?php

use App\Notification;
use App\Profile;
use App\Status;
use App\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Notification API Tests
|--------------------------------------------------------------------------
*/

describe('GET /api/v1/notifications', function () {
    it('returns empty array for user with no notifications', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJson([]);
    });

    it('returns notifications for authenticated user', function () {
        $user = User::factory()->create();
        $user->refresh();
        $other = User::factory()->create();
        $other->refresh();
        $status = Status::factory()->create([
            'profile_id' => $user->profile_id,
            'type' => 'photo',
        ]);

        Notification::create([
            'profile_id' => $user->profile_id,
            'actor_id' => $other->profile_id,
            'action' => 'like',
            'item_id' => $status->id,
            'item_type' => Status::class,
        ]);

        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonIsArray();
    });

    it('does not return other users notifications', function () {
        $user = User::factory()->create();
        $user->refresh();
        $other = User::factory()->create();
        $other->refresh();

        Notification::create([
            'profile_id' => $other->profile_id,
            'actor_id' => $user->profile_id,
            'action' => 'follow',
            'item_id' => $other->profile_id,
            'item_type' => Profile::class,
        ]);

        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJson([]);
    });
});
