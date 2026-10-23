<?php

use App\Models\Profile;
use App\Models\Story;
use App\Services\StoryIndexService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Story index author-key TTL
|--------------------------------------------------------------------------
|
| The per-author sorted set story:by_author:{id} must live as long as the
| author's LONGEST-lived active story. rebuildIndex() reindexes an author's
| stories newest-first, so the oldest (shortest-lived) story is indexed last.
| indexStory() must only ever extend the author-key TTL, never shorten it, so
| the key does not vanish (dropping the author from hasActiveStory/carousel)
| while newer stories are still active.
|
*/

function makeStory(Profile $author, Carbon\Carbon $createdAt, Carbon\Carbon $expiresAt): Story
{
    $story = new Story;
    $story->profile_id = $author->id;
    $story->type = 'photo';
    $story->duration = 3;
    $story->path = 'public/story/'.uniqid().'.jpg';
    $story->local = true;
    $story->active = true;
    $story->public = false;
    $story->view_count = 0;
    $story->created_at = $createdAt;
    $story->expires_at = $expiresAt;
    $story->save();

    return $story;
}

it('keeps the author-key TTL at the longest-lived story when the oldest is indexed last', function () {
    $author = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);

    // Start from a clean author key so a prior test cannot leak state.
    Redis::del("story:by_author:{$author->id}");

    // Story B: newer, ~25h remaining. Story A: older, ~4h remaining.
    $newer = makeStory($author, now()->subHour(), now()->addHours(24));
    $older = makeStory($author, now()->subHours(20), now()->addHours(4));

    $index = app(StoryIndexService::class);

    // Mirror rebuildIndex() ordering: newest-first, so the OLDER (shorter TTL)
    // story is indexed LAST -- the exact sequence that used to shorten the key.
    $index->indexStory($newer);
    $index->indexStory($older);

    $ttl = (int) Redis::ttl("story:by_author:{$author->id}");

    // Must reflect the newer story (~24h + 3600s buffer), not the older (~4h).
    // Comfortably above the older story's window, below the newer story's cap.
    expect($ttl)->toBeGreaterThan(4 * 3600 + 3600 + 60)
        ->and($ttl)->toBeLessThanOrEqual(24 * 3600 + 3600);

    // The author still has an active story regardless of index order.
    expect($index->hasActiveStory($author->id))->toBeTrue();
});

it('extends the author-key TTL when a newer story is indexed after an older one', function () {
    $author = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
    Redis::del("story:by_author:{$author->id}");

    $index = app(StoryIndexService::class);

    // Index a short-lived story first, then a longer-lived one.
    $short = makeStory($author, now()->subHours(20), now()->addHours(4));
    $index->indexStory($short);
    expect((int) Redis::ttl("story:by_author:{$author->id}"))
        ->toBeLessThanOrEqual(4 * 3600 + 3600);

    $long = makeStory($author, now(), now()->addHours(24));
    $index->indexStory($long);

    // TTL extended up to the longer story, never shortened back.
    expect((int) Redis::ttl("story:by_author:{$author->id}"))
        ->toBeGreaterThan(4 * 3600 + 3600 + 60);
});
