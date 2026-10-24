<?php

use App\Models\Collection;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| POST /api/local/collection/{id} visibility handling
|--------------------------------------------------------------------------
|
| collections.visibility is NOT NULL DEFAULT 'public'. store() must not accept
| an omitted visibility (which would write null: a 500 on strict DBs, or a
| stored '' -> permanent invisibility on non-strict MySQL). It should be
| required, matching publish() and the schema.
|
*/

/**
 * Create a published, public collection owned by $user.
 */
function makeCollection(User $user, string $visibility = 'public'): Collection
{
    $collection = new Collection;
    $collection->profile_id = $user->profile_id;
    $collection->title = 'Original title';
    $collection->description = 'Original description';
    $collection->visibility = $visibility;
    $collection->published_at = now();
    $collection->save();

    return $collection;
}

it('rejects an update that omits visibility with a 422 and leaves the row unchanged', function () {
    $user = User::factory()->create();
    $user->refresh();

    $collection = makeCollection($user, 'public');

    $this->actingAs($user)
        ->postJson("/api/local/collection/{$collection->id}", [
            'title' => 'Updated title',
            'description' => 'Updated description',
            // visibility intentionally omitted
        ])
        ->assertStatus(422);

    // The row must retain its valid visibility (never null / '').
    $fresh = $collection->fresh();
    expect($fresh->visibility)->toBe('public');
});

it('accepts an update that includes a valid visibility', function () {
    $user = User::factory()->create();
    $user->refresh();

    $collection = makeCollection($user, 'public');

    $this->actingAs($user)
        ->postJson("/api/local/collection/{$collection->id}", [
            'title' => 'Updated title',
            'description' => 'Updated description',
            'visibility' => 'private',
        ])
        ->assertOk();

    expect($collection->fresh()->visibility)->toBe('private');
});
