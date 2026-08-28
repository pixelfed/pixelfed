<?php

use App\Follower;
use App\Status;
use App\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Timeline API Tests
|--------------------------------------------------------------------------
*/

describe('public timeline', function () {
    it('returns public statuses', function () {
        $user = User::factory()->create();
        $user->refresh();

        Status::factory()->count(3)->create([
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
        ]);

        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/timelines/public')
            ->assertOk()
            ->assertJsonIsArray();
    });

    it('does not include private statuses in public timeline', function () {
        $user = User::factory()->create();
        $user->refresh();

        $private = Status::factory()->private()->create(['type' => 'photo']);

        Passport::actingAs($user, ['read']);

        $response = $this->getJson('/api/v1/timelines/public');
        $ids = collect($response->json())->pluck('id')->toArray();

        expect($ids)->not->toContain((string) $private->id);
    });

    it('supports limit parameter', function () {
        $user = User::factory()->create();
        $user->refresh();

        Status::factory()->count(5)->create([
            'type' => 'photo',
            'scope' => 'public',
        ]);

        Passport::actingAs($user, ['read']);

        $response = $this->getJson('/api/v1/timelines/public?limit=2');
        $response->assertOk();

        expect(count($response->json()))->toBeLessThanOrEqual(2);
    });
});

describe('home timeline', function () {
    it('returns statuses from followed accounts', function () {
        $user = User::factory()->create();
        $user->refresh();
        $followed = User::factory()->create();
        $followed->refresh();

        Follower::create([
            'profile_id' => $user->profile_id,
            'following_id' => $followed->profile_id,
        ]);

        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/timelines/home')
            ->assertOk()
            ->assertJsonIsArray();
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/timelines/home')
            ->assertUnauthorized();
    });
});

describe('hashtag timeline', function () {
    it('returns statuses for a hashtag', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/timelines/tag/pixelfed')
            ->assertOk()
            ->assertJsonIsArray();
    });
});
