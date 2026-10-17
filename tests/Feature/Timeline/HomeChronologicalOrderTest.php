<?php

use App\Models\Follower;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

afterEach(function () {
    Redis::flushdb();
});

it('ranks home posts by created_at even when a remote post has a newer id but older date', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $local = User::factory()->create();
    $local->refresh();
    Follower::create(['profile_id' => $pid, 'following_id' => $local->profile_id]);

    $remoteAuthor = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
    Follower::create(['profile_id' => $pid, 'following_id' => $remoteAuthor->id]);

    // A recent local post.
    $recent = Status::factory()->create([
        'profile_id' => $local->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'in_reply_to_id' => null, 'reblog_of_id' => null, 'uri' => null,
        'created_at' => now()->subHour(),
    ]);

    // A remote post ingested AFTER (higher id) but published weeks earlier. The
    // id is forced above the recent one so the ordering does not depend on the
    // sub-millisecond gap between two factory inserts.
    $staleRemote = Status::factory()->create([
        'id' => $recent->id + 1000,
        'profile_id' => $remoteAuthor->id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'in_reply_to_id' => null, 'reblog_of_id' => null,
        'uri' => 'https://remote.example/p/'.uniqid(),
        'created_at' => now()->subWeeks(4),
    ]);

    expect((int) $staleRemote->id)->toBeGreaterThan((int) $recent->id);

    StatusService::get($recent->id, false);
    StatusService::get($staleRemote->id, false);

    HomeTimelineService::warmCache($pid, true, 400, true);

    $ids = collect(HomeTimelineService::get($pid, 0, -1))->map(fn ($v) => (int) $v)->all();

    // The recent post sorts above the stale remote one despite its lower id.
    expect(array_search((int) $recent->id, $ids))
        ->toBeLessThan(array_search((int) $staleRemote->id, $ids));
    expect($ids[0])->toBe((int) $recent->id);
});
