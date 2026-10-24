<?php

use App\Models\Profile;
use App\Models\Story;
use App\Services\StoryIndexService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Story index rebuild — pagination data-skip
|--------------------------------------------------------------------------
|
| rebuildIndex() clears every story:* Redis key, then walks the live stories
| and recent story_views to repopulate the index. If that walk uses offset
| pagination (Laravel chunk()), a row dropping out of the filtered set
| mid-scan (a StoryExpire flipping active=false) shifts the remaining rows
| left, so the next offset jumps past a still-live story that is never
| re-indexed -- and since the cache was already wiped it stays missing for up
| to 24h with no self-heal. Keyset pagination (chunkById on the unique id)
| is immune: each page resumes from "id > last seen", not a positional offset.
|
*/

function makeChunkSkipStory(Profile $author, Carbon\Carbon $createdAt): Story
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
    $story->expires_at = now()->addHours(24);
    $story->save();

    return $story;
}

it('re-indexes every still-active story when an early row is removed mid-rebuild', function () {
    // Span more than one stories chunk (the rebuild walks 500 at a time), so
    // the mid-scan removal pushes a still-live row across the page boundary.
    $chunkSize = 500;
    $total = $chunkSize + 10;

    $author = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);

    $stories = [];
    for ($i = 0; $i < $total; $i++) {
        // Older-to-newer so ids (snowflake) increase with the loop. The rebuild
        // sorts by created_at DESC, so the NEWEST row sorts first (page 1).
        $stories[] = makeChunkSkipStory($author, now()->subMinutes($total - $i));
    }

    // The row we drop mid-scan sorts EARLY in the rebuild order (created_at
    // desc), so it lands on the first page -- read while still active, hence
    // indexed either way. Removing it from the filtered (active) set before the
    // second page is fetched shifts every later row one position left. Under
    // offset pagination the next page's OFFSET then jumps past a DIFFERENT,
    // still-live row that stays permanently missing. Keyset pagination resumes
    // from "id > last seen" and skips nothing.
    $victim = $stories[$total - 1]; // newest -> sorts first under created_at desc

    // Clear any leaked index state for this author.
    Redis::del("story:by_author:{$author->id}");

    $index = app(StoryIndexService::class);

    // Fire the mutation exactly once, after the first stories page has been
    // read, to emulate a StoryExpire landing between pages. Matching on the
    // paginated SELECT against the stories table keeps this deterministic
    // without coupling to the exact SQL text.
    $fired = false;
    DB::listen(function ($query) use (&$fired, $victim) {
        if ($fired) {
            return;
        }
        // Quote-agnostic match (backticks on MySQL/MariaDB, double quotes on
        // SQLite/Postgres) for the paginated SELECT over the stories table,
        // excluding the later story_views join query.
        $sql = strtolower($query->sql);
        $hitsStories = str_contains($sql, 'from `stories`') || str_contains($sql, 'from "stories"');
        if ($hitsStories && str_contains($sql, 'limit') && ! str_contains($sql, 'story_views')) {
            $fired = true;
            // Drop the early row out of the filtered (active) set.
            DB::table('stories')->where('id', $victim->id)->update(['active' => false]);
        }
    });

    $result = $index->rebuildIndex();

    DB::flushQueryLog();

    expect($result['status'])->toBe('success');

    // The invariant: every story still ACTIVE in the DB after the rebuild must
    // be present in the Redis index. This holds regardless of scan order or
    // which row was dropped. Under offset pagination the mid-scan removal shifts
    // the remaining rows left, so the next page's OFFSET jumps past a still-live
    // story that never gets indexed -- it stays active in the DB but missing
    // from Redis. Keyset pagination resumes from the last id and skips nothing.
    $stillActiveIds = DB::table('stories')
        ->where('active', true)
        ->where('expires_at', '>', now())
        ->pluck('id');

    foreach ($stillActiveIds as $id) {
        expect((bool) Redis::exists("story:{$id}"))->toBeTrue(
            "story:{$id} is still active in the DB but missing from the index"
        );
        $score = Redis::zscore("story:by_author:{$author->id}", (string) $id);
        expect($score)->not->toBeNull(
            "story {$id} is still active in the DB but missing from the author zset"
        );
    }

    // The author zset holds exactly the still-active stories -- no skips, no
    // stale rows.
    expect((int) Redis::zcard("story:by_author:{$author->id}"))
        ->toBe($stillActiveIds->count());

    // The dropped row must not be indexed.
    expect((bool) Redis::exists("story:{$victim->id}"))->toBeFalse();

    // Read-side signals the author still has a story.
    expect($index->hasActiveStory($author->id))->toBeTrue();
});

it('indexes all stories across chunk boundaries under a non-unique (profile_id, created_at) order', function () {
    // Guards the independent tied-row hazard: the first rebuild query orders by
    // (profile_id, created_at desc), which is NOT unique. chunk() can skip rows
    // on ties across a page boundary; chunkById's id tiebreaker cannot.
    $chunkSize = 500;
    $total = $chunkSize + 25;

    $author = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
    Redis::del("story:by_author:{$author->id}");

    // Identical created_at across every row forces the (profile_id, created_at)
    // sort to tie for the whole set -> ordering resolved only by the id
    // tiebreaker chunkById adds.
    $sharedCreatedAt = now()->subHour();
    $ids = [];
    for ($i = 0; $i < $total; $i++) {
        $ids[] = makeChunkSkipStory($author, $sharedCreatedAt)->id;
    }

    $index = app(StoryIndexService::class);
    $result = $index->rebuildIndex();

    expect($result['status'])->toBe('success');

    foreach ($ids as $id) {
        expect((bool) Redis::exists("story:{$id}"))->toBeTrue(
            "story:{$id} missing from index after rebuild"
        );
    }

    expect((int) Redis::zcard("story:by_author:{$author->id}"))->toBe($total);
    expect($index->hasActiveStory($author->id))->toBeTrue();
});
