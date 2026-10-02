<?php

use App\Jobs\HomeFeedPipeline\FeedInsertPipeline;
use App\Jobs\HomeFeedPipeline\FeedInsertRemotePipeline;
use App\Jobs\HomeFeedPipeline\FeedRemovePipeline;
use App\Jobs\HomeFeedPipeline\FeedRemoveRemotePipeline;
use App\Jobs\StatusPipeline\StatusEntityLexer;
use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\FollowerService;
use App\Services\HomeTimelineService;
use App\Services\InstanceService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use App\Util\ActivityPub\Helpers;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

// Each test runs a real pipeline or ingest path against a real status and asserts
// the resulting id is (or is not) a member of the home, public, or network cache.
// A Follower row plus FollowerService::add prime the caches the pipelines read, and
// StatusService::get warms the transformer cache before an insert runs.

$remoteUri = fn (int $id) => 'https://remote.example/users/alice/statuses/'.$id;

beforeEach(function () {
    config([
        'instance.timeline.network.cached' => true,
        'instance.timeline.home.max_backfill_days' => 180,
        'instance.hide_nsfw_on_public_feeds' => false,
    ]);

    Redis::del(PublicTimelineService::CACHE_KEY);
    Redis::del(NetworkTimelineService::CACHE_KEY);

    // Banned/unlisted domain lists are cached from the instances table; keep them
    // empty by default so an unrelated remote post is not filtered out, and so a
    // test that seeds a banned domain starts from a known-empty baseline.
    Cache::forget(InstanceService::CACHE_KEY_BANNED_DOMAINS);
    Cache::forget(InstanceService::CACHE_KEY_UNLISTED_DOMAINS);
});

afterEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
    Redis::del(NetworkTimelineService::CACHE_KEY);
    Cache::forget(InstanceService::CACHE_KEY_BANNED_DOMAINS);
    Cache::forget(InstanceService::CACHE_KEY_UNLISTED_DOMAINS);
});

/**
 * Register a local follower of $author so the fanout pipelines treat $follower
 * as a recipient: a persisted Follower row drives localFollowerIds, and
 * FollowerService::add primes the relationship caches.
 */
function followsLocally(User $follower, User $author): void
{
    Redis::del(HomeTimelineService::CACHE_KEY.$follower->profile_id);
    Follower::create([
        'profile_id' => $follower->profile_id,
        'following_id' => $author->profile_id,
        'local_profile' => true,
    ]);
    FollowerService::add($follower->profile_id, $author->profile_id);
}

function homeFeedIds(int $profileId): Collection
{
    return collect(HomeTimelineService::get($profileId, 0, -1))->map(fn ($i) => (string) $i);
}

function publicFeedIds(): Collection
{
    return collect(PublicTimelineService::get(0, -1))->map(fn ($i) => (string) $i);
}

function networkFeedIds(): Collection
{
    return collect(NetworkTimelineService::get(0, -1))->map(fn ($i) => (string) $i);
}

// Local user posts

it('adds an eligible local public photo to the public ZSET', function () {
    $author = User::factory()->create();
    $author->refresh();

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'local' => true, 'uri' => null, 'in_reply_to_id' => null, 'reblog_of_id' => null,
    ]);

    (new StatusEntityLexer($status))->deliver();

    expect(publicFeedIds())->toContain((string) $status->id);
});

it('keeps an unlisted local post out of the public ZSET', function () {
    $author = User::factory()->create();
    $author->refresh();

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'unlisted', 'visibility' => 'unlisted',
        'local' => true, 'uri' => null,
    ]);

    (new StatusEntityLexer($status))->deliver();

    expect(publicFeedIds())->not->toContain((string) $status->id);
});

it('fans an eligible local public post into a followers home ZSET', function () {
    $author = User::factory()->create();
    $follower = User::factory()->create();
    $author->refresh();
    $follower->refresh();

    followsLocally($follower, $author);

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'local' => true, 'uri' => null, 'in_reply_to_id' => null, 'reblog_of_id' => null,
    ]);
    StatusService::get($status->id, false);

    (new FeedInsertPipeline($status->id, $author->profile_id))->handle();

    expect(homeFeedIds($follower->profile_id))->toContain((string) $status->id);
    expect(homeFeedIds($author->profile_id))->toContain((string) $status->id);
});

it('does not add a local post to the network ZSET', function () {
    $author = User::factory()->create();
    $follower = User::factory()->create();
    $author->refresh();
    $follower->refresh();

    followsLocally($follower, $author);

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'local' => true, 'uri' => null,
    ]);
    StatusService::get($status->id, false);

    (new StatusEntityLexer($status))->deliver();
    (new FeedInsertPipeline($status->id, $author->profile_id))->handle();

    expect(networkFeedIds())->not->toContain((string) $status->id);
});

// Local delete

it('removes a deleted local post from the home ZSETs it was in', function () {
    $author = User::factory()->create();
    $follower = User::factory()->create();
    $author->refresh();
    $follower->refresh();

    followsLocally($follower, $author);

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'local' => true, 'uri' => null,
    ]);
    HomeTimelineService::add($author->profile_id, $status->id);
    HomeTimelineService::add($follower->profile_id, $status->id);

    (new FeedRemovePipeline($status->id, $author->profile_id))->handle();

    expect(homeFeedIds($author->profile_id))->not->toContain((string) $status->id);
    expect(homeFeedIds($follower->profile_id))->not->toContain((string) $status->id);
});

it('removes a deleted local post from the public ZSET', function () {
    $author = User::factory()->create();
    $author->refresh();

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'local' => true, 'uri' => null,
    ]);
    PublicTimelineService::add($status->id);

    PublicTimelineService::rem($status->id);

    expect(publicFeedIds())->not->toContain((string) $status->id);
});

// Remote post ingest

it('adds an eligible federated public post to the network ZSET', function () use ($remoteUri) {
    $author = User::factory()->create();
    $author->refresh();

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'local' => false, 'in_reply_to_id' => null, 'reblog_of_id' => null,
    ]);
    $status->uri = $remoteUri($status->id);
    $status->save();

    Helpers::handleStatusPostProcessing($status, $author->profile_id, $status->uri);

    expect(networkFeedIds())->toContain((string) $status->id);
});

it('rejects a banned-domain remote post from the network ZSET', function () use ($remoteUri) {
    Cache::put(InstanceService::CACHE_KEY_BANNED_DOMAINS, ['remote.example'], 60);

    $author = User::factory()->create();
    $author->refresh();

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'local' => false,
    ]);
    $status->uri = $remoteUri($status->id);
    $status->save();

    Helpers::handleStatusPostProcessing($status, $author->profile_id, $status->uri);

    expect(networkFeedIds())->not->toContain((string) $status->id);
});

it('rejects an nsfw remote post from the network ZSET when nsfw is hidden', function () use ($remoteUri) {
    config(['instance.hide_nsfw_on_public_feeds' => true]);

    $author = User::factory()->create();
    $author->refresh();

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'local' => false, 'is_nsfw' => true,
    ]);
    $status->uri = $remoteUri($status->id);
    $status->save();

    Helpers::handleStatusPostProcessing($status, $author->profile_id, $status->uri);

    expect(networkFeedIds())->not->toContain((string) $status->id);
});

it('fans a remote post into a local followers home ZSET', function () use ($remoteUri) {
    $author = User::factory()->create();
    $follower = User::factory()->create();
    $author->refresh();
    $follower->refresh();

    followsLocally($follower, $author);

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'local' => false, 'in_reply_to_id' => null, 'reblog_of_id' => null,
    ]);
    $status->uri = $remoteUri($status->id);
    $status->save();
    StatusService::get($status->id, false);

    (new FeedInsertRemotePipeline($status->id, $author->profile_id))->handle();

    expect(homeFeedIds($follower->profile_id))->toContain((string) $status->id);
});

it('does not fan a remote post into home when nobody follows the author', function () use ($remoteUri) {
    $author = User::factory()->create();
    $viewer = User::factory()->create();
    $author->refresh();
    $viewer->refresh();

    Redis::del(HomeTimelineService::CACHE_KEY.$viewer->profile_id);

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'local' => false,
    ]);
    $status->uri = $remoteUri($status->id);
    $status->save();
    StatusService::get($status->id, false);

    Helpers::handleStatusPostProcessing($status, $author->profile_id, $status->uri);
    (new FeedInsertRemotePipeline($status->id, $author->profile_id))->handle();

    expect(homeFeedIds($viewer->profile_id))->not->toContain((string) $status->id);
    expect(networkFeedIds())->toContain((string) $status->id);
});

it('does not fan a remote post older than the home backfill window into home', function () use ($remoteUri) {
    $author = User::factory()->create();
    $follower = User::factory()->create();
    $author->refresh();
    $follower->refresh();

    followsLocally($follower, $author);

    $old = now()->subDays((int) config('instance.timeline.home.max_backfill_days') + 10);

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'local' => false, 'created_at' => $old,
    ]);
    $status->uri = $remoteUri($status->id);
    $status->save();
    StatusService::get($status->id, false);

    expect(FeedInsertRemotePipeline::isTooOld($old))->toBeTrue();

    (new FeedInsertRemotePipeline($status->id, $author->profile_id))->handle();

    expect(homeFeedIds($follower->profile_id))->not->toContain((string) $status->id);
});

// Remote delete

it('removes an inbound-deleted remote post from the network ZSET', function () use ($remoteUri) {
    $author = User::factory()->create();
    $author->refresh();

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'local' => false,
    ]);
    $status->uri = $remoteUri($status->id);
    $status->save();
    NetworkTimelineService::add($status->id);

    NetworkTimelineService::rem($status->id);

    expect(networkFeedIds())->not->toContain((string) $status->id);
});

it('removes an inbound-deleted remote post from local followers home ZSETs', function () use ($remoteUri) {
    $author = User::factory()->create();
    $follower = User::factory()->create();
    $author->refresh();
    $follower->refresh();

    followsLocally($follower, $author);

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'local' => false,
    ]);
    $status->uri = $remoteUri($status->id);
    $status->save();
    HomeTimelineService::add($follower->profile_id, $status->id);

    (new FeedRemoveRemotePipeline($status->id, $author->profile_id))->handle();

    expect(homeFeedIds($follower->profile_id))->not->toContain((string) $status->id);
});

// Eligibility and warm-rebuild parity

it('includes a live-added local post after a fresh public warmCache rebuild (parity)', function () {
    $author = User::factory()->create();
    $author->refresh();

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'local' => true, 'uri' => null, 'in_reply_to_id' => null, 'reblog_of_id' => null,
    ]);

    (new StatusEntityLexer($status))->deliver();
    expect(publicFeedIds())->toContain((string) $status->id);

    PublicTimelineService::warmCache(true, 100);

    expect(publicFeedIds())->toContain((string) $status->id);
});

it('excludes a reply from the public feed via both the live path and warmCache (parity)', function () {
    $author = User::factory()->create();
    $author->refresh();

    $parent = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public', 'local' => true, 'uri' => null,
    ]);
    $reply = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'local' => true, 'uri' => null, 'in_reply_to_id' => $parent->id,
    ]);

    (new StatusEntityLexer($reply))->deliver();
    expect(publicFeedIds())->not->toContain((string) $reply->id);

    PublicTimelineService::warmCache(true, 100);

    expect(publicFeedIds())->not->toContain((string) $reply->id);
});

it('keeps a live-fanned reblog in the home ZSET after a warmCache rebuild', function () {
    $original = User::factory()->create();
    $sharer = User::factory()->create();
    $viewer = User::factory()->create();
    foreach ([$original, $sharer, $viewer] as $u) {
        $u->refresh();
    }

    followsLocally($viewer, $sharer);

    $orig = Status::factory()->create([
        'profile_id' => $original->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public', 'local' => true, 'uri' => null,
    ]);
    $boost = Status::factory()->create([
        'profile_id' => $sharer->profile_id,
        'type' => 'share', 'scope' => 'public', 'visibility' => 'public',
        'local' => true, 'uri' => null, 'reblog_of_id' => $orig->id,
    ]);
    StatusService::get($boost->id, false);

    (new FeedInsertPipeline($boost->id, $sharer->profile_id))->handle();
    expect(homeFeedIds($viewer->profile_id))->toContain((string) $boost->id);

    HomeTimelineService::warmCache($viewer->profile_id, true, 100);

    expect(homeFeedIds($viewer->profile_id))->toContain((string) $boost->id);
});

it('rejects an unlisted remote post from the network feed via both the live gate and warmCache', function () use ($remoteUri) {
    $author = User::factory()->create();
    $author->refresh();

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'unlisted', 'visibility' => 'unlisted',
        'local' => false, 'in_reply_to_id' => null, 'reblog_of_id' => null,
    ]);
    $status->uri = $remoteUri($status->id);
    $status->save();

    expect(Helpers::isEligibleForNetwork($status))->toBeFalse();

    Helpers::handleStatusPostProcessing($status, $author->profile_id, $status->uri);
    expect(networkFeedIds())->not->toContain((string) $status->id);

    NetworkTimelineService::warmCache(true, 100);

    expect(networkFeedIds())->not->toContain((string) $status->id);
});

it('reconciles a follow-then-unfollow ghost out of the ex-followers home ZSET on warmCache rebuild', function () {
    $author = User::factory()->create();
    $follower = User::factory()->create();
    $author->refresh();
    $follower->refresh();

    followsLocally($follower, $author);

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'local' => true, 'uri' => null, 'in_reply_to_id' => null, 'reblog_of_id' => null,
    ]);
    StatusService::get($status->id, false);
    (new FeedInsertPipeline($status->id, $author->profile_id))->handle();
    expect(homeFeedIds($follower->profile_id))->toContain((string) $status->id);

    // Unfollow: the per-post FeedRemovePipeline does not sweep the ex-follower
    // (it iterates the CURRENT follower set), so the id lingers until a rebuild.
    Follower::where('profile_id', $follower->profile_id)
        ->where('following_id', $author->profile_id)
        ->delete();
    FollowerService::remove($follower->profile_id, $author->profile_id);
    Cache::forget('profile:following:'.$follower->profile_id);

    HomeTimelineService::warmCache($follower->profile_id, true, 100);

    expect(homeFeedIds($follower->profile_id))->not->toContain((string) $status->id);
});
