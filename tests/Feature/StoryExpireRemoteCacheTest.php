<?php

use App\Jobs\StoryPipeline\StoryExpire;
use App\Models\Profile;
use App\Models\Story;
use App\Models\User;
use App\Services\StoryService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

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

it('invalidates the author latest cache when a remote story expires', function () {
    $user = User::factory()->create();
    $author = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);

    $story = makeRemoteStory($author);
    $cacheKey = StoryService::STORY_KEY.'latest:pid-'.$author->id;

    // Warm the cache.
    expect(StoryService::latest($author->id))->toBe($story->id);
    expect(Cache::has($cacheKey))->toBeTrue();

    (new StoryExpire($story))->handle();

    // The row is gone and the cache no longer points at the deleted id.
    expect(Story::find($story->id))->toBeNull();
    expect(Cache::has($cacheKey))->toBeFalse();

    // Re-resolving now returns null (author has no stories) without crashing.
    expect(StoryService::latest($author->id))->toBeNull();
});

it('latest() returns null for an author with no stories', function () {
    $author = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);

    expect(StoryService::latest($author->id))->toBeNull();
});
