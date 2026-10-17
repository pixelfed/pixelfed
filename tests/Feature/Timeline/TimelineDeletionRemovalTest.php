<?php

use App\Jobs\DeletePipeline\DeleteRemoteStatusPipeline;
use App\Jobs\HomeFeedPipeline\FeedRemovePipeline;
use App\Jobs\HomeFeedPipeline\FeedRemoveRemotePipeline;
use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Models\Follower;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Observers\StatusObserver;
use App\Services\FollowerService;
use App\Services\HomeTimelineService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Deletion removal behaviour
|--------------------------------------------------------------------------
|
| A deletion must clear the status from every cache it was ever fanned into
| while leaving its neighbours untouched and in the same display order. These
| walks drive the real entry points - the status observer, the two home-feed
| removal jobs, and the remote force-delete jobs - after the owning write has
| committed, rather than poking the timeline services directly. They assert the
| deleted id is gone everywhere it belonged, the survivors keep their exact
| ZREVRANGE order, the queued job carries the right status and profile ids, and
| running the cleanup twice changes nothing the second time.
|
*/

beforeEach(function () {
    config(['federation.network_timeline' => true]);
    config(['instance.hide_nsfw_on_public_feeds' => false]);
    Redis::flushdb();
    Cache::flush();
});

afterEach(function () {
    Redis::flushdb();
    Cache::flush();
});

/**
 * Exact highest-score-first order Redis serves the set in. Survivor oracles
 * compare against this so a low id with a recent date lands where it displays.
 */
function removalDisplayOrder(string $key): array
{
    return collect(Redis::zrevrange($key, 0, -1))
        ->map(fn ($v) => (string) $v)
        ->values()
        ->all();
}

/**
 * A local public photo with an explicit created_at. Resolving it through
 * StatusService primes the created_at score the timeline services read.
 */
function localRemovalStatus(int $authorPid, Carbon $createdAt): Status
{
    $status = Status::factory()->create([
        'profile_id' => $authorPid,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => true,
        'uri' => null,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'is_nsfw' => false,
        'created_at' => $createdAt,
    ]);

    StatusService::get($status->id, false);
    StatusService::getMastodon($status->id, false);

    return $status;
}

/**
 * A remote public photo with an explicit created_at and a remote uri so it is
 * eligible for the network feed and routed down the remote removal paths.
 */
function remoteRemovalStatus(int $authorPid, string $domain, Carbon $createdAt): Status
{
    $status = Status::factory()->create([
        'profile_id' => $authorPid,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => false,
        'uri' => 'https://'.$domain.'/p/'.bin2hex(random_bytes(6)),
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'is_nsfw' => false,
        'created_at' => $createdAt,
    ]);

    StatusService::get($status->id, false);
    StatusService::getMastodon($status->id, false);

    return $status;
}

it('removes a deleted local status from self and follower home caches while preserving unrelated members', function () {
    $author = User::factory()->create();
    $author->refresh();
    $authorPid = $author->profile_id;

    $follower = User::factory()->create();
    $follower->refresh();
    $followerPid = $follower->profile_id;

    $bystander = User::factory()->create();
    $bystander->refresh();
    $bystanderPid = $bystander->profile_id;

    Follower::create(['profile_id' => $followerPid, 'following_id' => $authorPid]);

    // The follower list is read through a short-lived cache inside the removal
    // job; prime it so the committed follower relationship is visible.
    expect(FollowerService::localFollowerIds($authorPid))->toContain($followerPid);

    $older = localRemovalStatus($authorPid, now()->subMinutes(30));
    $target = localRemovalStatus($authorPid, now()->subMinutes(20));
    $newer = localRemovalStatus($authorPid, now()->subMinutes(10));

    // Fan the three posts into the author's own home feed and the follower's
    // home feed. The bystander follows nobody, so its feed holds only its own
    // unrelated member and must be left untouched.
    $bystanderOwn = localRemovalStatus($bystanderPid, now()->subMinutes(15));

    foreach ([$authorPid, $followerPid] as $pid) {
        foreach ([$older, $target, $newer] as $status) {
            HomeTimelineService::add($pid, $status->id);
        }
    }
    HomeTimelineService::add($bystanderPid, $bystanderOwn->id);

    $authorKey = HomeTimelineService::CACHE_KEY.$authorPid;
    $followerKey = HomeTimelineService::CACHE_KEY.$followerPid;
    $bystanderKey = HomeTimelineService::CACHE_KEY.$bystanderPid;

    // Display order before deletion: newest-dated first in both subscribed feeds.
    $expectedBefore = [(string) $newer->id, (string) $target->id, (string) $older->id];
    expect(removalDisplayOrder($authorKey))->toBe($expectedBefore);
    expect(removalDisplayOrder($followerKey))->toBe($expectedBefore);

    // The observer picks the local removal job and carries status + profile ids.
    Bus::fake([FeedRemovePipeline::class, FeedRemoveRemotePipeline::class]);

    (new StatusObserver)->deleted($target);

    Bus::assertNotDispatched(FeedRemoveRemotePipeline::class);
    Bus::assertDispatched(FeedRemovePipeline::class, function ($job) use ($target, $authorPid) {
        return (new ReflectionClass($job))->getProperty('sid')->getValue($job) == $target->id
            && (new ReflectionClass($job))->getProperty('pid')->getValue($job) == $authorPid;
    });

    // Run the queued job for real against the warm caches.
    (new FeedRemovePipeline($target->id, $authorPid))->handle();

    $expectedAfter = [(string) $newer->id, (string) $older->id];

    // The deleted id is gone from the author feed and every follower feed.
    expect(removalDisplayOrder($authorKey))->toBe($expectedAfter);
    expect(removalDisplayOrder($followerKey))->toBe($expectedAfter);
    expect(Redis::zscore($authorKey, $target->id))->toBeFalse();
    expect(Redis::zscore($followerKey, $target->id))->toBeFalse();

    // An unrelated viewer's feed is never touched.
    expect(removalDisplayOrder($bystanderKey))->toBe([(string) $bystanderOwn->id]);

    // Running the cleanup again is a no-op: same ordered sets, no survivor lost.
    (new FeedRemovePipeline($target->id, $authorPid))->handle();
    expect(removalDisplayOrder($authorKey))->toBe($expectedAfter);
    expect(removalDisplayOrder($followerKey))->toBe($expectedAfter);
    expect(removalDisplayOrder($bystanderKey))->toBe([(string) $bystanderOwn->id]);
});

it('removes a force-deleted remote status from network and direct-follower home caches while preserving unrelated members', function () {
    $remoteAuthor = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
    $remotePid = $remoteAuthor->id;

    $follower = User::factory()->create();
    $follower->refresh();
    $followerPid = $follower->profile_id;

    $bystander = User::factory()->create();
    $bystander->refresh();
    $bystanderPid = $bystander->profile_id;

    Follower::create(['profile_id' => $followerPid, 'following_id' => $remotePid]);
    expect(FollowerService::localFollowerIds($remotePid))->toContain($followerPid);

    $older = remoteRemovalStatus($remotePid, 'remote.example', now()->subMinutes(30));
    $target = remoteRemovalStatus($remotePid, 'remote.example', now()->subMinutes(20));
    $newer = remoteRemovalStatus($remotePid, 'remote.example', now()->subMinutes(10));

    // An unrelated remote post from a different author shares the network feed
    // and must survive the deletion untouched.
    $otherRemote = remoteRemovalStatus(
        Profile::factory()->create(['user_id' => null, 'domain' => 'other.example'])->id,
        'other.example',
        now()->subMinutes(5)
    );

    foreach ([$older, $target, $newer, $otherRemote] as $status) {
        NetworkTimelineService::add($status->id);
    }

    // The direct follower also sees the remote author's posts in their home feed.
    foreach ([$older, $target, $newer] as $status) {
        HomeTimelineService::add($followerPid, $status->id);
    }

    $networkKey = NetworkTimelineService::CACHE_KEY;
    $followerKey = HomeTimelineService::CACHE_KEY.$followerPid;

    $expectedNetworkBefore = [
        (string) $otherRemote->id,
        (string) $newer->id,
        (string) $target->id,
        (string) $older->id,
    ];
    expect(removalDisplayOrder($networkKey))->toBe($expectedNetworkBefore);
    expect(removalDisplayOrder($followerKey))->toBe([
        (string) $newer->id, (string) $target->id, (string) $older->id,
    ]);

    // The observer routes a remote deletion to the remote home-removal job with
    // the right status + profile ids.
    Bus::fake([FeedRemovePipeline::class, FeedRemoveRemotePipeline::class]);

    (new StatusObserver)->deleted($target);

    Bus::assertNotDispatched(FeedRemovePipeline::class);
    Bus::assertDispatched(FeedRemoveRemotePipeline::class, function ($job) use ($target, $remotePid) {
        return (new ReflectionClass($job))->getProperty('sid')->getValue($job) == $target->id
            && (new ReflectionClass($job))->getProperty('pid')->getValue($job) == $remotePid;
    });

    // Drive the remote force-delete entry path. DeleteRemoteStatusPipeline runs
    // StatusService::del(id, true), which prunes the public and network feeds.
    (new DeleteRemoteStatusPipeline(Status::findOrFail($target->id)))->handle();

    // And run the home-feed removal job that the observer queued.
    (new FeedRemoveRemotePipeline($target->id, $remotePid))->handle();

    $expectedNetworkAfter = [
        (string) $otherRemote->id, (string) $newer->id, (string) $older->id,
    ];
    $expectedFollowerAfter = [(string) $newer->id, (string) $older->id];

    expect(removalDisplayOrder($networkKey))->toBe($expectedNetworkAfter);
    expect(removalDisplayOrder($followerKey))->toBe($expectedFollowerAfter);
    expect(Redis::zscore($networkKey, $target->id))->toBeFalse();
    expect(Redis::zscore($followerKey, $target->id))->toBeFalse();

    // Idempotent: a second pass through the remote home-removal path changes
    // nothing on either affected cache.
    (new FeedRemoveRemotePipeline($target->id, $remotePid))->handle();

    expect(removalDisplayOrder($networkKey))->toBe($expectedNetworkAfter);
    expect(removalDisplayOrder($followerKey))->toBe($expectedFollowerAfter);

    // The second remote force-delete entry path, RemoteStatusDelete, must also
    // clear the network (and public) feed. Seed a fresh public+network pair and
    // delete the middle member through that job.
    PublicTimelineService::warmCache(true, 400);
    $publicKey = PublicTimelineService::CACHE_KEY;

    $pOlder = remoteRemovalStatus($remotePid, 'remote.example', now()->subMinutes(9));
    $pTarget = remoteRemovalStatus($remotePid, 'remote.example', now()->subMinutes(8));
    $pNewer = remoteRemovalStatus($remotePid, 'remote.example', now()->subMinutes(7));

    foreach ([$pOlder, $pTarget, $pNewer] as $status) {
        NetworkTimelineService::add($status->id);
        PublicTimelineService::add($status->id);
    }

    expect(Redis::zscore($networkKey, $pTarget->id))->not->toBeFalse();
    expect(Redis::zscore($publicKey, $pTarget->id))->not->toBeFalse();

    (new RemoteStatusDelete(Status::findOrFail($pTarget->id)))->handle();

    expect(Redis::zscore($networkKey, $pTarget->id))->toBeFalse();
    expect(Redis::zscore($publicKey, $pTarget->id))->toBeFalse();
    expect(removalDisplayOrder($networkKey))->toContain((string) $pNewer->id, (string) $pOlder->id);
    expect(removalDisplayOrder($networkKey))->not->toContain((string) $pTarget->id);
});
