<?php

use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

afterEach(function () {
    Redis::flushdb();
});

function warmHomeStatus(int $profileId): Status
{
    return Status::factory()->create([
        'profile_id' => $profileId,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'uri' => null,
    ]);
}

it('represents every followed account even when one posts far more', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $prolific = User::factory()->create();
    $prolific->refresh();
    $quiet = User::factory()->create();
    $quiet->refresh();

    foreach ([$prolific, $quiet] as $u) {
        Follower::create(['profile_id' => $pid, 'following_id' => $u->profile_id]);
    }

    // Quiet account posts first (older ids), prolific account floods afterwards.
    $quietStatus = warmHomeStatus($quiet->profile_id);
    for ($i = 0; $i < 10; $i++) {
        warmHomeStatus($prolific->profile_id);
    }

    // Warm with a cap smaller than the prolific account's output so the flat
    // "newest N" query alone would never include the quiet account.
    HomeTimelineService::warmCache($pid, true, 5);

    $cached = collect(HomeTimelineService::get($pid, 0, -1))
        ->map(fn ($v) => (int) $v)
        ->all();

    expect($cached)->toContain((int) $quietStatus->id);
});

it('keeps the newest posts as well as the per-account latest', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $author = User::factory()->create();
    $author->refresh();
    Follower::create(['profile_id' => $pid, 'following_id' => $author->profile_id]);

    $ids = collect(range(1, 6))
        ->map(fn () => (int) warmHomeStatus($author->profile_id)->id)
        ->sortDesc()
        ->values();

    HomeTimelineService::warmCache($pid, true, 4);

    $cached = collect(HomeTimelineService::get($pid, 0, -1))->map(fn ($v) => (int) $v)->sortDesc()->values()->all();

    // The four newest are present.
    expect(array_slice($cached, 0, 4))->toBe($ids->take(4)->all());
});
