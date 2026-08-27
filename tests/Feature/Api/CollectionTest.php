<?php

use App\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Collection Tests
|--------------------------------------------------------------------------
*/

describe('collections', function () {
    it('lists self collections', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/pixelfed/v1/collections/self')
            ->assertOk()
            ->assertJsonIsArray();
    });

    it('lists user collections by account id', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson("/api/pixelfed/v1/collections/accounts/{$user->profile_id}")
            ->assertOk()
            ->assertJsonIsArray();
    });

    it('returns 404 for non-existent collection', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/pixelfed/v1/collections/view/999999')
            ->assertNotFound();
    });

    it('requires authentication for self collections', function () {
        $this->getJson('/api/pixelfed/v1/collections/self')
            ->assertUnauthorized();
    });
});
