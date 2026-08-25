<?php

namespace Tests\Feature\Api;

use App\Follower;
use App\Profile;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RemoveFollowerScopeTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithProfile(): User
    {
        $user = User::factory()->create();
        $profile = Profile::create([
            'user_id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
        ]);
        $user->profile_id = $profile->id;
        $user->save();

        return $user;
    }

    #[Test]
    public function remove_follower_requires_follow_scope()
    {
        $alice = $this->createUserWithProfile();
        $bob = $this->createUserWithProfile();

        Follower::withoutEvents(function () use ($alice, $bob) {
            Follower::create([
                'profile_id' => $bob->profile_id,
                'following_id' => $alice->profile_id,
            ]);
        });

        // Alice tries to remove Bob with a read-only token — should be denied
        Passport::actingAs($alice, ['read']);

        $response = $this->postJson("/api/v1/accounts/{$bob->profile_id}/remove_from_followers");

        $response->assertStatus(403);

        // Verify follower was NOT removed
        $this->assertDatabaseHas('followers', [
            'profile_id' => $bob->profile_id,
            'following_id' => $alice->profile_id,
        ]);
    }

    #[Test]
    public function remove_follower_denied_with_write_scope_only()
    {
        $alice = $this->createUserWithProfile();
        $bob = $this->createUserWithProfile();

        Follower::withoutEvents(function () use ($alice, $bob) {
            Follower::create([
                'profile_id' => $bob->profile_id,
                'following_id' => $alice->profile_id,
            ]);
        });

        // Alice tries to remove Bob with a write token (no follow scope) — should be denied
        Passport::actingAs($alice, ['write']);

        $response = $this->postJson("/api/v1/accounts/{$bob->profile_id}/remove_from_followers");

        $response->assertStatus(403);

        $this->assertDatabaseHas('followers', [
            'profile_id' => $bob->profile_id,
            'following_id' => $alice->profile_id,
        ]);
    }
}
