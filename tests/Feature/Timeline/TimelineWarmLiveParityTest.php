<?php

use App\Jobs\HomeFeedPipeline\FeedInsertPipeline;
use App\Models\Follower;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\InstanceService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use App\Util\ActivityPub\Helpers;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Warm-versus-live membership parity
|--------------------------------------------------------------------------
|
| Each feed has two ways a sorted set gets populated: live insertion as posts
| arrive, and a forced warm that rebuilds the set from the database. These
| tests feed the identical fixtures through both paths and assert that the
| complete ordered Redis set is byte-for-byte identical afterwards, so a warm
| never adds, drops, or reorders a member relative to live delivery.
|
| Fixtures deliberately include ineligible posts (replies, boosts of the same
| author, private scope, unsupported types, sensitive posts under both
| hide-nsfw settings, posts past the age boundary, banned/unlisted remote
| domains, and shadow-filtered local accounts) so both paths must agree on
| exclusion as well as inclusion.
|
*/

const BANNED_DOMAINS_CACHE_KEY = 'instances:banned:domains';

const UNLISTED_DOMAINS_CACHE_KEY = 'instances:unlisted:domains';

const SHADOW_FILTER_CACHE_KEY = 'pf:services:asfs:list:hide_from_public_feeds';

beforeEach(function () {
    config(['instance.timeline.network.max_hours_old' => 2160]);

    Cache::forget(BANNED_DOMAINS_CACHE_KEY);
    Cache::forget(UNLISTED_DOMAINS_CACHE_KEY);
    Cache::forget(SHADOW_FILTER_CACHE_KEY);
});

afterEach(function () {
    Redis::flushdb();
    Cache::forget(BANNED_DOMAINS_CACHE_KEY);
    Cache::forget(UNLISTED_DOMAINS_CACHE_KEY);
    Cache::forget(SHADOW_FILTER_CACHE_KEY);
});

/**
 * The ordered sorted-set members, newest-first, as integers.
 */
function orderedSet(string $key): array
{
    return collect(Redis::zrevrange($key, 0, -1))
        ->map(fn ($id) => (int) $id)
        ->values()
        ->all();
}

/**
 * A local public photo that both the live local feed and warmCache accept.
 */
function parityLocalStatus(int $profileId, string $createdAt, array $overrides = []): Status
{
    $status = Status::factory()->create(array_merge([
        'profile_id' => $profileId,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => true,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'is_nsfw' => false,
        'uri' => null,
        'created_at' => $createdAt,
    ], $overrides));

    StatusService::get($status->id, false);

    return $status;
}

/**
 * A remote public photo keyed to a specific domain, id, and publish time.
 */
function parityRemoteStatus(string $domain, string $createdAt, array $overrides = []): Status
{
    $author = Profile::factory()->create(['user_id' => null, 'domain' => $domain]);

    $status = Status::factory()->create(array_merge([
        'profile_id' => $author->id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => false,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'is_nsfw' => false,
        'uri' => 'https://'.$domain.'/p/'.uniqid(),
        'created_at' => $createdAt,
    ], $overrides));

    StatusService::get($status->id, false);

    return $status;
}

it('keeps home membership and order identical after force warming live-fanned follows and boosts', function () {
    config(['instance.hide_nsfw_on_public_feeds' => false]);

    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $local = User::factory()->create();
    $local->refresh();
    $remote = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
    $boosted = User::factory()->create();
    $boosted->refresh();

    foreach ([$local->profile_id, $remote->id, $boosted->profile_id] as $fid) {
        Follower::create(['profile_id' => $pid, 'following_id' => $fid]);
    }

    // Eligible members from followed accounts, distinct seconds pin order.
    // The home feed carries private posts from follows, so a private photo is
    // a legitimate member alongside the public local and remote photos.
    $localPost = Status::factory()->create([
        'profile_id' => $local->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'in_reply_to_id' => null, 'reblog_of_id' => null, 'uri' => null,
        'created_at' => now()->subMinutes(10),
    ]);
    $privatePost = Status::factory()->create([
        'profile_id' => $local->profile_id,
        'type' => 'photo', 'scope' => 'private', 'visibility' => 'private',
        'in_reply_to_id' => null, 'reblog_of_id' => null, 'uri' => null,
        'created_at' => now()->subMinutes(14),
    ]);
    $remotePost = Status::factory()->create([
        'profile_id' => $remote->id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'in_reply_to_id' => null, 'reblog_of_id' => null,
        'local' => false, 'uri' => 'https://remote.example/p/'.uniqid(),
        'created_at' => now()->subMinutes(20),
    ]);
    $original = Status::factory()->create([
        'profile_id' => $boosted->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'in_reply_to_id' => null, 'reblog_of_id' => null, 'uri' => null,
        'created_at' => now()->subMinutes(30),
    ]);

    // Fixtures neither path should surface: a reply, a boost authored by an
    // unfollowed account, and an unfollowed stranger's photo. Warm never
    // reconstructs a boost, and fan-out never reaches a viewer who follows
    // neither the sharer nor, here, the original author.
    $reply = Status::factory()->create([
        'profile_id' => $local->profile_id,
        'type' => 'reply', 'scope' => 'public', 'visibility' => 'public',
        'in_reply_to_id' => $original->id, 'reblog_of_id' => null, 'uri' => null,
        'created_at' => now()->subMinutes(12),
    ]);
    $stranger = User::factory()->create();
    $stranger->refresh();
    $boost = Status::factory()->create([
        'profile_id' => $stranger->profile_id,
        'type' => 'share', 'scope' => 'public', 'visibility' => 'public',
        'in_reply_to_id' => null, 'reblog_of_id' => $original->id, 'uri' => null,
        'created_at' => now()->subMinutes(8),
    ]);
    $strangerPost = Status::factory()->create([
        'profile_id' => $stranger->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'in_reply_to_id' => null, 'reblog_of_id' => null, 'uri' => null,
        'created_at' => now()->subMinutes(16),
    ]);

    $candidates = [$localPost, $privatePost, $remotePost, $original, $reply, $boost, $strangerPost];

    foreach ($candidates as $s) {
        StatusService::get($s->id, false);
    }

    // Expected display order is newest-first by publication time across the
    // followed, in-feed members.
    $eligibleIds = collect([$localPost, $privatePost, $remotePost, $original])
        ->sortByDesc('created_at')
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->values()
        ->all();

    // Live path: fan-out only writes to a viewer whose home key already
    // exists, so seed a throwaway sentinel to create the key, deliver every
    // candidate exactly as the pipeline would, then drop the sentinel before
    // the snapshot. Real members keep the key alive after removal.
    $sentinel = 1;
    HomeTimelineService::add($pid, $sentinel);

    foreach ($candidates as $s) {
        (new FeedInsertPipeline($s->id, $s->profile_id))->handle();
    }

    HomeTimelineService::rem($pid, $sentinel);

    $liveOrder = orderedSet(HomeTimelineService::CACHE_KEY.$pid);

    // Warm path: rebuild from scratch and compare.
    Redis::del(HomeTimelineService::CACHE_KEY.$pid);
    HomeTimelineService::warmCache($pid, true, 400);
    $warmOrder = orderedSet(HomeTimelineService::CACHE_KEY.$pid);

    expect($warmOrder)->toBe($liveOrder);
    expect($warmOrder)->toBe($eligibleIds);

    foreach ([$reply, $boost, $strangerPost] as $excluded) {
        expect($warmOrder)->not->toContain((int) $excluded->id);
    }
});

it('keeps local membership and order identical after force warming every eligibility fixture', function () {
    config(['instance.hide_nsfw_on_public_feeds' => true]);

    $author = User::factory()->create();
    $author->refresh();
    $aid = $author->profile_id;

    $shadow = User::factory()->create();
    $shadow->refresh();
    Cache::put(SHADOW_FILTER_CACHE_KEY, [$shadow->profile_id], 86400);

    // Eligible local photos under the hide-nsfw setting, distinct seconds.
    $eligible = collect([
        parityLocalStatus($aid, now()->subMinutes(5)->toDateTimeString()),
        parityLocalStatus($aid, now()->subMinutes(15)->toDateTimeString()),
        parityLocalStatus($aid, now()->subMinutes(25)->toDateTimeString()),
    ]);
    $eligibleIds = $eligible->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

    // Ineligible: reply, boost, private, unsupported type, sensitive,
    // remote (has uri), and a shadow-filtered local account.
    $reply = parityLocalStatus($aid, now()->subMinutes(6)->toDateTimeString(), [
        'type' => 'reply', 'in_reply_to_id' => $eligible->first()->id,
    ]);
    $boost = parityLocalStatus($aid, now()->subMinutes(7)->toDateTimeString(), [
        'type' => 'share', 'reblog_of_id' => $eligible->first()->id,
    ]);
    $private = parityLocalStatus($aid, now()->subMinutes(8)->toDateTimeString(), [
        'scope' => 'private', 'visibility' => 'private',
    ]);
    $unsupported = parityLocalStatus($aid, now()->subMinutes(9)->toDateTimeString(), [
        'type' => 'text',
    ]);
    $sensitive = parityLocalStatus($aid, now()->subMinutes(11)->toDateTimeString(), [
        'is_nsfw' => true,
    ]);
    $remote = parityLocalStatus($aid, now()->subMinutes(13)->toDateTimeString(), [
        'local' => false, 'uri' => 'https://remote.example/p/'.uniqid(),
    ]);
    $shadowPost = parityLocalStatus($shadow->profile_id, now()->subMinutes(4)->toDateTimeString());

    // Live path: add through the service exactly as delivery would, honoring
    // the same eligibility gates the delivery pipeline applies.
    foreach ($eligible as $s) {
        PublicTimelineService::add($s->id);
    }

    $liveOrder = orderedSet(PublicTimelineService::CACHE_KEY);

    // Warm path.
    Redis::del(PublicTimelineService::CACHE_KEY);
    PublicTimelineService::warmCache(true, 400);
    $warmOrder = orderedSet(PublicTimelineService::CACHE_KEY);

    expect($warmOrder)->toBe($liveOrder);
    expect($warmOrder)->toBe($eligibleIds);

    foreach ([$reply, $boost, $private, $unsupported, $sensitive, $remote, $shadowPost] as $excluded) {
        expect($warmOrder)->not->toContain((int) $excluded->id);
    }
});

// With the cap set to two and three eligible remote posts whose id order and
// publication-time order are fully reversed, the two population paths select
// their survivors by different keys: the forced warm keeps the two highest ids
// while live insertion keeps the two most recent by publication time. This
// asserts strict membership equality on purpose, so any divergence between the
// two cap-selection strategies is caught here rather than reaching production.
it('keeps network membership and order identical after force warming when id and publication order disagree', function () {
    config(['instance.hide_nsfw_on_public_feeds' => true]);
    config(['instance.timeline.network.cache_dropoff' => 2]);

    // Three eligible remote statuses whose id order and publication-time order
    // oppose each other: the highest id is the oldest post, the lowest id is
    // the newest post.
    $high = parityRemoteStatus('remote.example', now()->subMinutes(90)->toDateTimeString(), ['id' => 900000000000000003]);
    $mid = parityRemoteStatus('remote.example', now()->subMinutes(60)->toDateTimeString(), ['id' => 900000000000000002]);
    $low = parityRemoteStatus('remote.example', now()->subMinutes(30)->toDateTimeString(), ['id' => 900000000000000001]);

    // Ineligible fixtures neither path should surface. They carry ids below
    // the eligible trio so the warm's id-ranked selection does not pick a
    // filtered row ahead of the eligible candidates.
    $reply = parityRemoteStatus('remote.example', now()->subMinutes(35)->toDateTimeString(), [
        'id' => 800000000000000001, 'type' => 'reply', 'in_reply_to_id' => $low->id,
    ]);
    $private = parityRemoteStatus('remote.example', now()->subMinutes(40)->toDateTimeString(), [
        'id' => 800000000000000002, 'scope' => 'private', 'visibility' => 'private',
    ]);
    $unsupported = parityRemoteStatus('remote.example', now()->subMinutes(45)->toDateTimeString(), [
        'id' => 800000000000000003, 'type' => 'text',
    ]);
    $sensitive = parityRemoteStatus('remote.example', now()->subMinutes(50)->toDateTimeString(), [
        'id' => 800000000000000004, 'is_nsfw' => true,
    ]);
    $tooOld = parityRemoteStatus('remote.example', now()->subHours(3000)->toDateTimeString(), [
        'id' => 800000000000000005,
    ]);
    $banned = parityRemoteStatus('banned.example', now()->subMinutes(25)->toDateTimeString(), [
        'id' => 800000000000000006,
    ]);
    $unlisted = parityRemoteStatus('unlisted.example', now()->subMinutes(20)->toDateTimeString(), [
        'id' => 800000000000000007,
    ]);

    Cache::put(BANNED_DOMAINS_CACHE_KEY, ['banned.example'], 1209600);
    Cache::put(UNLISTED_DOMAINS_CACHE_KEY, ['unlisted.example'], 1209600);

    // Live path: the ingest helper applies the same eligibility and filtered
    // domain checks delivery would, trimming to cache_dropoff as it adds.
    foreach ([$high, $mid, $low, $reply, $private, $unsupported, $sensitive, $tooOld, $banned, $unlisted] as $s) {
        Helpers::handleStatusPostProcessing($s->fresh(), $s->profile_id, $s->uri);
    }

    $liveOrder = orderedSet(NetworkTimelineService::CACHE_KEY);

    // Warm path.
    Redis::del(NetworkTimelineService::CACHE_KEY);
    NetworkTimelineService::warmCache(true, 2);
    $warmOrder = orderedSet(NetworkTimelineService::CACHE_KEY);

    expect($warmOrder)->toBe($liveOrder);
    expect(count($warmOrder))->toBe(2);

    foreach ([$reply, $private, $unsupported, $sensitive, $tooOld, $banned, $unlisted] as $excluded) {
        expect($warmOrder)->not->toContain((int) $excluded->id);
    }
});
