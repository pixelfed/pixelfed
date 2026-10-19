<?php

use App\Models\Profile;
use App\Models\Status;
use App\Services\NetworkTimelineService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['instance.hide_nsfw_on_public_feeds' => false]);
    config(['instance.timeline.network.max_hours_old' => 2160]);
    Redis::del(NetworkTimelineService::CACHE_KEY);
});

afterEach(function () {
    Redis::del(NetworkTimelineService::CACHE_KEY);
});

function remoteStatusWithId(int $id, string $createdAt): Status
{
    $author = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);

    return Status::factory()->create([
        'id' => $id,
        'profile_id' => $author->id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => false,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'is_nsfw' => false,
        'uri' => 'https://remote.example/p/'.$id,
        'created_at' => $createdAt,
    ]);
}

it('warms the highest-id rows so the cache matches the ranked order', function () {
    // Highest id is the newest in the feed, but it was published (created_at)
    // earlier than a lower-id row. A limit of 2 must keep the two highest ids.
    remoteStatusWithId(100, now()->subHours(1)->toDateTimeString());   // newest created_at, lowest id
    remoteStatusWithId(200, now()->subHours(10)->toDateTimeString());
    remoteStatusWithId(300, now()->subHours(20)->toDateTimeString());  // oldest created_at, highest id

    NetworkTimelineService::warmCache(true, 2);

    $cached = collect(NetworkTimelineService::get(0, -1))->map(fn ($v) => (int) $v)->sort()->values()->all();

    expect($cached)->toBe([200, 300]);
});
