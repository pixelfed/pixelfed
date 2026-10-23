<?php

use App\Models\Bookmark;
use App\Models\Status;
use App\Models\User;
use App\Services\BookmarkService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Legacy Bookmark Removal Tests
|--------------------------------------------------------------------------
|
| Bookmarks created before the create gates were tightened can point at a
| reply, reblog or non-media status. Removal must still succeed even though
| those statuses would be rejected by the create gates.
|
*/

describe('removing legacy bookmarks', function () {
    it('removes a legacy bookmark on a reply via the web toggle', function () {
        $user = User::factory()->create();
        $user->refresh();

        $reply = Status::factory()->create([
            'profile_id' => $user->profile_id,
            'type' => 'reply',
            'in_reply_to_id' => Status::factory()->create(['type' => 'photo', 'scope' => 'public'])->id,
            'scope' => 'public',
            'visibility' => 'public',
        ]);

        Bookmark::create([
            'status_id' => $reply->id,
            'profile_id' => $user->profile_id,
        ]);
        BookmarkService::add($user->profile_id, $reply->id);

        $this->actingAs($user)
            ->postJson('/i/bookmark', ['item' => $reply->id])
            ->assertOk();

        expect(Bookmark::where('status_id', $reply->id)->where('profile_id', $user->profile_id)->exists())->toBeFalse();
        expect(BookmarkService::get($user->profile_id, $reply->id))->toBeFalse();
    });

    it('removes a legacy bookmark on a reblog via the api', function () {
        $user = User::factory()->create();
        $user->refresh();

        $status = Status::factory()->create([
            'profile_id' => $user->profile_id,
            'type' => 'reply',
            'reblog_of_id' => Status::factory()->create(['type' => 'photo', 'scope' => 'public'])->id,
            'scope' => 'public',
            'visibility' => 'public',
        ]);

        Bookmark::create([
            'status_id' => $status->id,
            'profile_id' => $user->profile_id,
        ]);
        BookmarkService::add($user->profile_id, $status->id);

        Passport::actingAs($user, ['write']);

        $this->postJson("/api/v1/statuses/{$status->id}/unbookmark")
            ->assertOk()
            ->assertJsonFragment(['bookmarked' => false]);

        expect(Bookmark::where('status_id', $status->id)->where('profile_id', $user->profile_id)->exists())->toBeFalse();
        expect(BookmarkService::get($user->profile_id, $status->id))->toBeFalse();
    });

    it('still bookmarks a normal eligible photo status', function () {
        $user = User::factory()->create();
        $user->refresh();

        $status = Status::factory()->create([
            'profile_id' => $user->profile_id,
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
        ]);

        Passport::actingAs($user, ['write']);

        $this->postJson("/api/v1/statuses/{$status->id}/bookmark")
            ->assertOk()
            ->assertJsonFragment(['bookmarked' => true]);

        expect(Bookmark::where('status_id', $status->id)->where('profile_id', $user->profile_id)->exists())->toBeTrue();
    });

    it('still blocks creating a bookmark on an ineligible status', function () {
        $user = User::factory()->create();
        $user->refresh();

        $reply = Status::factory()->create([
            'profile_id' => $user->profile_id,
            'type' => 'reply',
            'in_reply_to_id' => Status::factory()->create(['type' => 'photo', 'scope' => 'public'])->id,
            'scope' => 'public',
            'visibility' => 'public',
        ]);

        $this->actingAs($user)
            ->postJson('/i/bookmark', ['item' => $reply->id])
            ->assertNotFound();

        Passport::actingAs($user, ['write']);

        $this->postJson("/api/v1/statuses/{$reply->id}/bookmark")
            ->assertNotFound();

        expect(Bookmark::where('status_id', $reply->id)->where('profile_id', $user->profile_id)->exists())->toBeFalse();
    });
});
