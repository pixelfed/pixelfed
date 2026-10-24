<?php

use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

describe('PUT /api/v1/statuses/{id} non-public edit', function () {
    it('returns the full status body when editing an unlisted post', function () {
        $user = User::factory()->create();
        $user->refresh();
        $status = Status::factory()->unlisted()->create([
            'profile_id' => $user->profile_id,
            'type' => 'text',
            'caption' => 'original unlisted caption',
        ]);
        Passport::actingAs($user, ['write', 'read']);

        $response = $this->putJson("/api/v1/statuses/{$status->id}", [
            'status' => 'edited unlisted caption',
        ]);

        $response->assertOk()
            ->assertJsonFragment(['id' => (string) $status->id])
            ->assertJsonFragment(['content_text' => 'edited unlisted caption']);

        expect($response->json())->not->toBeNull()->not->toBeEmpty();
        expect($response->json('content'))->not->toBeEmpty();
    });

    it('returns the full status body when editing a private post', function () {
        $user = User::factory()->create();
        $user->refresh();
        $status = Status::factory()->private()->create([
            'profile_id' => $user->profile_id,
            'type' => 'text',
            'caption' => 'original private caption',
        ]);
        Passport::actingAs($user, ['write', 'read']);

        $response = $this->putJson("/api/v1/statuses/{$status->id}", [
            'status' => 'edited private caption',
        ]);

        $response->assertOk()
            ->assertJsonFragment(['id' => (string) $status->id])
            ->assertJsonFragment(['content_text' => 'edited private caption']);

        expect($response->json())->not->toBeNull()->not->toBeEmpty();
        expect($response->json('content'))->not->toBeEmpty();
    });

    it('returns the full status body when editing a public post', function () {
        $user = User::factory()->create();
        $user->refresh();
        $status = Status::factory()->create([
            'profile_id' => $user->profile_id,
            'type' => 'text',
            'caption' => 'original public caption',
        ]);
        Passport::actingAs($user, ['write', 'read']);

        $response = $this->putJson("/api/v1/statuses/{$status->id}", [
            'status' => 'edited public caption',
        ]);

        $response->assertOk()
            ->assertJsonFragment(['id' => (string) $status->id])
            ->assertJsonFragment(['content_text' => 'edited public caption']);

        expect($response->json())->not->toBeNull()->not->toBeEmpty();
        expect($response->json('content'))->not->toBeEmpty();
    });
});
