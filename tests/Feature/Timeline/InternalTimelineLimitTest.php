<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['federation.network_timeline' => true]);
});

it('rejects a non-positive limit on the local timeline', function () {
    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->getJson('/api/pixelfed/v1/timelines/public?limit=-5')
        ->assertStatus(422);

    $this->actingAs($user)
        ->getJson('/api/pixelfed/v1/timelines/public?limit=0')
        ->assertStatus(422);
});

it('rejects a non-positive limit on the network timeline', function () {
    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->getJson('/api/pixelfed/v1/timelines/network?limit=-5')
        ->assertStatus(422);
});
