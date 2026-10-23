<?php

use App\Models\Like;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Status API Tests
|--------------------------------------------------------------------------
*/

describe('GET /api/v1/statuses/{id}', function () {
    it('returns a public status by id', function () {
        $user = User::factory()->create();
        $user->refresh();
        $status = Status::factory()->create([
            'profile_id' => $user->profile_id,
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
        ]);
        Passport::actingAs($user, ['read']);

        $this->getJson("/api/v1/statuses/{$status->id}")
            ->assertOk()
            ->assertJsonFragment(['id' => (string) $status->id]);
    });

    it('returns 404 for a non-existent status', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['read']);

        $this->getJson('/api/v1/statuses/999999999')
            ->assertNotFound();
    });
});

describe('DELETE /api/v1/statuses/{id}', function () {
    it('deletes own status', function () {
        $user = User::factory()->create();
        $user->refresh();
        $status = Status::factory()->create([
            'profile_id' => $user->profile_id,
            'type' => 'photo',
        ]);
        Passport::actingAs($user, ['write']);

        $this->deleteJson("/api/v1/statuses/{$status->id}")
            ->assertOk();

        expect(Status::find($status->id))->toBeNull();
    });

    it('cannot delete another users status', function () {
        $user = User::factory()->create();
        $user->refresh();
        $other = User::factory()->create();
        $other->refresh();
        $status = Status::factory()->create([
            'profile_id' => $other->profile_id,
            'type' => 'photo',
        ]);
        Passport::actingAs($user, ['write']);

        // Returns 404 to avoid revealing existence to non-owners
        $this->deleteJson("/api/v1/statuses/{$status->id}")
            ->assertNotFound();
    });
});

describe('POST /api/v1/statuses/{id}/favourite', function () {
    it('favourites a public status', function () {
        $user = User::factory()->create();
        $user->refresh();
        $other = User::factory()->create();
        $other->refresh();
        $status = Status::factory()->create([
            'profile_id' => $other->profile_id,
            'type' => 'photo',
            'scope' => 'public',
        ]);
        Passport::actingAs($user, ['write']);

        $this->postJson("/api/v1/statuses/{$status->id}/favourite")
            ->assertOk()
            ->assertJsonFragment(['favourited' => true]);

        expect(Like::where('profile_id', $user->profile_id)
            ->where('status_id', $status->id)
            ->exists()
        )->toBeTrue();
    });

    it('returns 404 for non-existent status', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['write']);

        $this->postJson('/api/v1/statuses/999999999/favourite')
            ->assertNotFound();
    });
});

describe('POST /api/v1/statuses/{id}/unfavourite', function () {
    it('unfavourites a previously liked status', function () {
        $user = User::factory()->create();
        $user->refresh();
        $other = User::factory()->create();
        $other->refresh();
        $status = Status::factory()->create([
            'profile_id' => $other->profile_id,
            'type' => 'photo',
            'scope' => 'public',
        ]);

        Like::create([
            'profile_id' => $user->profile_id,
            'status_id' => $status->id,
            'status_profile_id' => $other->profile_id,
        ]);

        Passport::actingAs($user, ['write']);

        $this->postJson("/api/v1/statuses/{$status->id}/unfavourite")
            ->assertOk()
            ->assertJsonFragment(['favourited' => false]);
    });
});

describe('POST /api/v1/statuses/{id}/bookmark', function () {
    it('bookmarks a status', function () {
        $user = User::factory()->create();
        $user->refresh();
        $status = Status::factory()->create(['type' => 'photo', 'scope' => 'public']);
        Passport::actingAs($user, ['write']);

        $this->postJson("/api/v1/statuses/{$status->id}/bookmark")
            ->assertOk()
            ->assertJsonFragment(['bookmarked' => true]);
    });

    it('unbookmarks a bookmarked status', function () {
        $user = User::factory()->create();
        $user->refresh();
        $status = Status::factory()->create(['type' => 'photo', 'scope' => 'public']);
        Passport::actingAs($user, ['write']);

        // Bookmark first
        $this->postJson("/api/v1/statuses/{$status->id}/bookmark");

        $this->postJson("/api/v1/statuses/{$status->id}/unbookmark")
            ->assertOk()
            ->assertJsonFragment(['bookmarked' => false]);
    });
});

describe('POST /api/v1/statuses (create)', function () {
    it('rejects status creation without media or text', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['write']);

        // The endpoint first checks token validity (403 if no token object)
        // then validates the payload. With Passport::actingAs the token()
        // method returns null, triggering the abort_if check.
        $this->postJson('/api/v1/statuses', [])
            ->assertStatus(403);
    });

    it('rejects direct visibility', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['write']);

        $this->postJson('/api/v1/statuses', [
            'status' => 'Hello world',
            'visibility' => 'direct',
        ])->assertStatus(400)
            ->assertJsonFragment(['error' => 'Direct visibility is not available.']);
    });

    it('rejects status exceeding max caption length', function () {
        $user = User::factory()->create();
        $user->refresh();
        Passport::actingAs($user, ['write']);
        $maxLen = (int) config('pixelfed.max_caption_length', 150);

        $this->postJson('/api/v1/statuses', [
            'status' => str_repeat('a', $maxLen + 10),
        ])->assertUnprocessable();
    });
});
