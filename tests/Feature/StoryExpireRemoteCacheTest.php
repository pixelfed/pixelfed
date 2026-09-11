<?php

use App\Jobs\StoryPipeline\StoryExpire;
use App\Models\Profile;
use App\Models\Story;
use App\Services\StoryIndexService;
use App\Services\StoryService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Remote story expiry cache hygiene
|--------------------------------------------------------------------------
|
| Expiring a remote (federated/cached) story must invalidate the per-author
| latest-story cache, matching the local expiry path. StoryService::latest()
| must also tolerate an author with no stories (return null, not fatal).
|
*/

function makeRemoteStory(Profile $author): Story
{
    $story = new Story;
    $story->profile_id = $author->id;
    $story->type = 'photo';
    $story->duration = 3;
    $story->path = null;
    $story->local = false;
    $story->active = true;
    $story->public = false;
    $story->view_count = 0;
    $story->expires_at = now()->subMinute();
    $story->save();

    return $story;
}

it('invalidates both story caches when a remote story expires', function () {
    $author = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
    $story = makeRemoteStory($author);
    $story->forceFill([
        'active' => true,
        'expires_at' => now()->addHours(24),
    ])->save();
    $index = app(StoryIndexService::class);
    $cacheKey = StoryService::STORY_KEY.'latest:pid-'.$author->id;

    // Index is empty, so this warms the SQL-backed cache.
    expect(StoryService::latest($author->id))->toBe($story->id);
    expect(Cache::has($cacheKey))->toBeTrue();

    // Now index it; latest() answers from Redis from here on.
    $index->indexStory($story);
    expect($index->hasActiveStory($author->id))->toBeTrue();
    expect(StoryService::latest($author->id))->toBe($story->id);

    $this->travel(25)->hours();
    (new StoryExpire($story))->handle();

    // Row gone, SQL cache cleared.
    expect(Story::find($story->id))->toBeNull();
    expect(Cache::has($cacheKey))->toBeFalse();

    // Index actually pruned, not just outside the 24h window.
    expect(Redis::exists("story:{$story->id}"))->toBeFalsy();
    expect(Redis::sismember('story:active_authors', (string) $author->id))->toBeFalsy();
    expect($index->hasActiveStory($author->id))->toBeFalse();

    // Re-resolving returns null without crashing.
    expect(StoryService::latest($author->id))->toBeNull();
});

it('latest() returns null for an author with no stories', function () {
    $author = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);

    expect(StoryService::latest($author->id))->toBeNull();
});
