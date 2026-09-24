<?php

use App\Models\Hashtag;
use App\Models\Media;
use App\Models\Status;
use App\Models\StatusHashtag;
use App\Models\User;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Discover hashtag feed pagination
|--------------------------------------------------------------------------
|
| GET /api/v2/discover/tag paginates a hashtag feed 9-per-page. The offset
| formula yielded 18 for both page 1 and page 2, so both pages returned the
| same rows and the freshest 18 posts were unreachable. The offset must be
| (page - 1) * 9: page 1 -> newest 9, page 2 -> next 9, disjoint.
|
*/

beforeEach(function () {
    Redis::spy();
});

/**
 * A public, media-bearing status tagged with $hashtag. The discover feed only
 * returns rows with public visibility and non-empty media_attachments.
 */
function taggedMediaStatus(int $profileId, Hashtag $hashtag, ?int $ageSeconds = null): Status
{
    $attributes = [
        'profile_id' => $profileId,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
    ];

    // Distinct timestamps so latest() (created_at DESC) is deterministic; the
    // suite creates rows within the same second otherwise.
    if ($ageSeconds !== null) {
        $attributes['created_at'] = now()->subSeconds($ageSeconds);
    }

    $status = Status::factory()->create($attributes);

    $media = new Media;
    $media->status_id = $status->id;
    $media->profile_id = $profileId;
    $media->media_path = 'public/test.jpg';
    $media->mime = 'image/jpeg';
    $media->order = 1;
    $media->save();

    StatusHashtag::create([
        'status_id' => $status->id,
        'hashtag_id' => $hashtag->id,
        'profile_id' => $profileId,
        'status_visibility' => 'public',
    ]);

    StatusService::del($status->id);

    return $status;
}

function discoverPage(object $test, User $user, string $tag, int $page): array
{
    return $test->actingAs($user)
        ->getJson("/api/v2/discover/tag?hashtag={$tag}&page={$page}")
        ->assertOk()
        ->json('tags');
}

it('returns disjoint, newest-first pages for a hashtag feed', function () {
    $user = User::factory()->create();
    $user->refresh();

    $hashtag = Hashtag::create(['name' => 'pixelfed', 'slug' => 'pixelfed']);

    // 27 tagged posts with strictly decreasing age, so index 0 is the newest.
    $newestFirst = [];
    foreach (range(0, 26) as $i) {
        $newestFirst[] = taggedMediaStatus($user->profile_id, $hashtag, $i);
    }

    $page1 = collect(discoverPage($this, $user, 'pixelfed', 1))
        ->pluck('status.id')->map(fn ($v) => (string) $v);
    $page2 = collect(discoverPage($this, $user, 'pixelfed', 2))
        ->pluck('status.id')->map(fn ($v) => (string) $v);

    // Each page is a full window of 9.
    expect($page1)->toHaveCount(9);
    expect($page2)->toHaveCount(9);

    // Pages are disjoint (the pre-fix bug returned identical rows).
    expect($page1->intersect($page2))->toBeEmpty();

    // Page 1 is the newest 9 (the pre-fix bug skipped the freshest 18).
    $expectedPage1 = collect(array_slice($newestFirst, 0, 9))->map(fn ($s) => (string) $s->id);
    expect($page1->values()->all())->toBe($expectedPage1->values()->all());

    // Page 2 is the next 9.
    $expectedPage2 = collect(array_slice($newestFirst, 9, 9))->map(fn ($s) => (string) $s->id);
    expect($page2->values()->all())->toBe($expectedPage2->values()->all());
});

it('returns the freshest post on page 1', function () {
    $user = User::factory()->create();
    $user->refresh();

    $hashtag = Hashtag::create(['name' => 'pixelfed', 'slug' => 'pixelfed']);

    $newest = taggedMediaStatus($user->profile_id, $hashtag, 0);
    foreach (range(1, 9) as $i) {
        taggedMediaStatus($user->profile_id, $hashtag, $i);
    }

    $page1 = collect(discoverPage($this, $user, 'pixelfed', 1))
        ->pluck('status.id')->map(fn ($v) => (string) $v);

    expect($page1)->toContain((string) $newest->id);
});
