<?php

use App\Models\Status;
use App\Models\User;
use App\Services\NetworkTimelineService;
use App\Services\SnowflakeService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

// The network cache is scored by status id. For federated posts the authored
// time and the local id are not monotonic, so warming must order by id, not
// created_at, or the feed drops and reorders posts.

beforeEach(function () {
    config([
        'instance.timeline.network.cached' => true,
        'instance.timeline.network.cache_dropoff' => 50,
        // Wide horizon so this suite isolates ordering, not the recency cutoff.
        'instance.timeline.network.max_hours_old' => 24 * 90,
        'instance.hide_nsfw_on_public_feeds' => false,
    ]);

    Redis::del(NetworkTimelineService::CACHE_KEY);
    Cache::forget('api:v1:timelines:network:cache_check');
});

afterEach(function () {
    Redis::del(NetworkTimelineService::CACHE_KEY);
});

// Seeds a remote public photo with id and created_at set independently.
function seedRemoteStatus(int $pid, int $id, Carbon $createdAt): int
{
    Status::factory()->create([
        'id' => $id,
        'profile_id' => $pid,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => false,
        'uri' => 'https://remote.example/users/alice/statuses/'.$id,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'created_at' => $createdAt,
    ]);

    return $id;
}

describe('network warm ordering', function () {
    it('warms the ZSET by id-desc, not created_at-desc, when the two axes disagree', function () {
        $author = User::factory()->create();
        $author->refresh();
        $pid = $author->profile_id;

        // Four posts inside the horizon whose id order (A,B,C,D) is the reverse of
        // their authored-time order (C,B,D,A), so the two sort axes clearly disagree.
        $idA = (int) SnowflakeService::byDate(now()->subHours(1));
        $idB = (int) SnowflakeService::byDate(now()->subHours(2));
        $idC = (int) SnowflakeService::byDate(now()->subHours(3));
        $idD = (int) SnowflakeService::byDate(now()->subHours(4));

        seedRemoteStatus($pid, $idA, now()->subDays(40));  // oldest authored, newest id
        seedRemoteStatus($pid, $idB, now()->subDays(2));
        seedRemoteStatus($pid, $idC, now()->subHours(1));  // newest authored, 3rd id
        seedRemoteStatus($pid, $idD, now()->subDays(10));

        NetworkTimelineService::warmCache(true, 50);

        $warmed = collect(NetworkTimelineService::get(0, -1))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $expectedByIdDesc = collect([$idA, $idB, $idC, $idD])->sortDesc()->values()->all();
        $createdAtDesc = [$idC, $idB, $idD, $idA];

        // Order follows the id axis (ZSET score), not created_at.
        expect($warmed)->toBe($expectedByIdDesc);
        expect($warmed)->not->toBe($createdAtDesc);

        // The backdated-but-newer-id post sits above the recent-but-older-id post.
        expect(array_search($idA, $warmed))->toBeLessThan(array_search($idC, $warmed));

        // No eligible post is dropped: every seeded id is a ZSET member.
        foreach ([$idA, $idB, $idC, $idD] as $id) {
            expect($warmed)->toContain($id);
        }
    });

    it('shares the id axis with backfillOlder: a max_id keyset returns the next-lower id, not the next-lower created_at', function () {
        $author = User::factory()->create();
        $author->refresh();
        $pid = $author->profile_id;

        $idHigh = (int) SnowflakeService::byDate(now()->subHours(1));
        $idMid = (int) SnowflakeService::byDate(now()->subHours(2));
        $idLow = (int) SnowflakeService::byDate(now()->subHours(3));

        // The next-lower id ($idMid) is authored MORE recently than the lowest id
        // ($idLow): created_at keyset would pick the wrong row. id keyset must
        // return $idMid first.
        seedRemoteStatus($pid, $idHigh, now()->subDays(5));
        seedRemoteStatus($pid, $idMid, now()->subHours(1));
        seedRemoteStatus($pid, $idLow, now()->subDays(20));

        $returned = NetworkTimelineService::backfillOlder($idHigh, 10);

        expect($returned)->toBe([$idMid, $idLow]);
        expect($returned[0])->toBe($idMid);
    });
});
