<?php

use App\Models\Status;
use App\Models\User;
use App\Services\PublicTimelineService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
});

afterEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
});

function warmLocalStatus(int $profileId): Status
{
    return Status::factory()->create([
        'profile_id' => $profileId,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => true,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'uri' => null,
    ]);
}

it('keeps every post when an author has more than one in the window', function () {
    $authorA = User::factory()->create();
    $authorA->refresh();
    $authorB = User::factory()->create();
    $authorB->refresh();

    $seeded = collect([
        warmLocalStatus($authorA->profile_id),
        warmLocalStatus($authorA->profile_id),
        warmLocalStatus($authorA->profile_id),
        warmLocalStatus($authorB->profile_id),
        warmLocalStatus($authorB->profile_id),
    ])->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();

    PublicTimelineService::warmCache(true, 400);

    $cached = collect(PublicTimelineService::get(0, -1))
        ->map(fn ($id) => (int) $id)
        ->sort()
        ->values()
        ->all();

    expect($cached)->toBe($seeded);
});

it('serves the warmed set newest-first', function () {
    $author = User::factory()->create();
    $author->refresh();

    $ids = collect(range(1, 4))
        ->map(fn () => (int) warmLocalStatus($author->profile_id)->id)
        ->all();

    PublicTimelineService::warmCache(true, 400);

    $cached = collect(PublicTimelineService::get(0, -1))->map(fn ($id) => (int) $id)->all();

    expect($cached)->toBe(collect($ids)->sortDesc()->values()->all());
});
