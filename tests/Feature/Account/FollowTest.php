<?php

use App\Follower;
use App\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Follow/Unfollow Tests
|--------------------------------------------------------------------------
*/

describe('follow via API', function () {
    it('follows a public account', function () {
        $user = User::factory()->create();
        $user->refresh();
        $target = User::factory()->create();
        $target->refresh();
        Passport::actingAs($user, ['write', 'follow']);

        $this->postJson("/api/v1/accounts/{$target->profile_id}/follow")
            ->assertOk()
            ->assertJsonFragment(['following' => true]);

        expect(Follower::where('profile_id', $user->profile_id)
            ->where('following_id', $target->profile_id)
            ->exists()
        )->toBeTrue();
    });

    it('unfollows a followed account', function () {
        $user = User::factory()->create();
        $user->refresh();
        $target = User::factory()->create();
        $target->refresh();

        Follower::create([
            'profile_id' => $user->profile_id,
            'following_id' => $target->profile_id,
        ]);

        Passport::actingAs($user, ['write', 'follow']);

        $this->postJson("/api/v1/accounts/{$target->profile_id}/unfollow")
            ->assertOk()
            ->assertJsonFragment(['following' => false]);

        expect(Follower::where('profile_id', $user->profile_id)
            ->where('following_id', $target->profile_id)
            ->exists()
        )->toBeFalse();
    });

    it('cannot follow yourself', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['write', 'follow']);

        $this->postJson("/api/v1/accounts/{$user->profile_id}/follow")
            ->assertStatus(400);
    });

    it('returns 404 for non-existent account', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['write', 'follow']);

        $this->postJson('/api/v1/accounts/999999999/follow')
            ->assertNotFound();
    });

    it('returns followers list for an account', function () {
        $user = User::factory()->create();
        $user->refresh();
        $follower = User::factory()->create();
        $follower->refresh();

        Follower::create([
            'profile_id' => $follower->profile_id,
            'following_id' => $user->profile_id,
        ]);

        Passport::actingAs($user, ['read']);

        $this->getJson("/api/v1/accounts/{$user->profile_id}/followers")
            ->assertOk()
            ->assertJsonIsArray();
    });

    it('returns following list for an account', function () {
        $user = User::factory()->create();
        $user->refresh();
        $target = User::factory()->create();
        $target->refresh();

        Follower::create([
            'profile_id' => $user->profile_id,
            'following_id' => $target->profile_id,
        ]);

        Passport::actingAs($user, ['read']);

        $this->getJson("/api/v1/accounts/{$user->profile_id}/following")
            ->assertOk()
            ->assertJsonIsArray();
    });
});
