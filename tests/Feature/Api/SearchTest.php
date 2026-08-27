<?php

use App\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Search API Tests
|--------------------------------------------------------------------------
*/

describe('GET /api/v2/search', function () {
    it('requires authentication', function () {
        $this->getJson('/api/v2/search?q=test')
            ->assertUnauthorized();
    });

    it('returns results structure for a query', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v2/search?q=test')
            ->assertOk()
            ->assertJsonStructure(['accounts', 'statuses', 'hashtags']);
    });

    it('rejects empty query', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v2/search?q=')
            ->assertStatus(422);
    });

    it('finds local accounts by username', function () {
        $target = User::factory()->create(['username' => 'searchable']);
        $target->refresh();
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $response = $this->getJson('/api/v2/search?q=searchable&type=accounts');
        $response->assertOk();

        $accounts = $response->json('accounts');
        $usernames = collect($accounts)->pluck('username')->toArray();
        expect($usernames)->toContain('searchable');
    });
});
