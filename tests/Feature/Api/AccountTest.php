<?php

use App\Status;
use App\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Account API Tests
|--------------------------------------------------------------------------
*/

describe('GET /api/v1/accounts/verify_credentials', function () {
    it('returns the authenticated users account', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/accounts/verify_credentials')
            ->assertOk()
            ->assertJsonStructure(['id', 'username', 'acct', 'display_name', 'note'])
            ->assertJsonFragment(['username' => $user->username]);
    });
});

describe('GET /api/v1/accounts/{id}', function () {
    it('returns a public account by id', function () {
        $user = User::factory()->create();
        $user->refresh();
        $targetUser = User::factory()->create();
        $targetUser->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson("/api/v1/accounts/{$targetUser->profile_id}")
            ->assertOk()
            ->assertJsonFragment(['username' => $targetUser->username]);
    });

    it('returns 404 for a non-existent account', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/accounts/999999999')
            ->assertNotFound();
    });
});

describe('GET /api/v1/accounts/{id}/statuses', function () {
    it('returns statuses for a public account', function () {
        $user = User::factory()->create();
        $user->refresh();
        $targetUser = User::factory()->create();
        $targetUser->refresh();
        Status::factory()->count(3)->create([
            'profile_id' => $targetUser->profile_id,
            'type' => 'photo',
        ]);
        Passport::actingAs($user, ['read']);

        $this->getJson("/api/v1/accounts/{$targetUser->profile_id}/statuses")
            ->assertOk()
            ->assertJsonIsArray();
    });
});

describe('GET /api/v1/accounts/{id}/followers', function () {
    it('returns followers for an account', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson("/api/v1/accounts/{$user->profile_id}/followers")
            ->assertOk()
            ->assertJsonIsArray();
    });
});

describe('GET /api/v1/accounts/{id}/following', function () {
    it('returns following list for an account', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson("/api/v1/accounts/{$user->profile_id}/following")
            ->assertOk()
            ->assertJsonIsArray();
    });
});

describe('GET /api/v1/accounts/relationships', function () {
    it('returns relationship info for given accounts', function () {
        $user = User::factory()->create();
        $user->refresh();
        $other = User::factory()->create();
        $other->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson("/api/v1/accounts/relationships?id[]={$other->profile_id}")
            ->assertOk()
            ->assertJsonIsArray();
    });
});
