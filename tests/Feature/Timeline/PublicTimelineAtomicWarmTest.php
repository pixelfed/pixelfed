<?php

use App\Models\Status;
use App\Models\User;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['instance.hide_nsfw_on_public_feeds' => false]);
    Redis::del(PublicTimelineService::CACHE_KEY);
});

afterEach(function () {
    foreach (Redis::keys(PublicTimelineService::CACHE_KEY.'*') as $k) {
        Redis::del(str_replace(config('database.redis.options.prefix', ''), '', $k));
    }
});

function atomicLocalStatus(int $profileId): Status
{
    $s = Status::factory()->create([
        'profile_id' => $profileId,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => true,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'uri' => null,
    ]);
    StatusService::get($s->id);

    return $s;
}

it('rebuilds the warm set without leaving a rebuild temp key behind', function () {
    $author = User::factory()->create();
    $author->refresh();

    $ids = collect(range(1, 5))
        ->map(fn () => (int) atomicLocalStatus($author->profile_id)->id)
        ->sort()
        ->values()
        ->all();

    // Pre-populate with stale content, then force a rebuild.
    PublicTimelineService::add(999999999);
    PublicTimelineService::warmCache(true, 400);

    $cached = collect(PublicTimelineService::get(0, -1))->map(fn ($v) => (int) $v)->sort()->values()->all();
    expect($cached)->toBe($ids);

    $leaked = collect(Redis::keys(PublicTimelineService::CACHE_KEY.':rebuild:*'));
    expect($leaked)->toBeEmpty();
});

it('clears the set when no eligible posts remain', function () {
    PublicTimelineService::add(999999999);

    PublicTimelineService::warmCache(true, 400);

    expect(PublicTimelineService::count())->toBe(0);
});
