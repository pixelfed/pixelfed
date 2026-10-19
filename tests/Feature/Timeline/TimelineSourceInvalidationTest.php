<?php

use App\Jobs\HomeFeedPipeline\FeedRemoveDomainPipeline;
use App\Jobs\HomeFeedPipeline\FeedUnfollowPipeline;
use App\Jobs\HomeFeedPipeline\HashtagUnfollowPipeline;
use App\Models\Follower;
use App\Models\Hashtag;
use App\Models\HashtagFollow;
use App\Models\Profile;
use App\Models\Status;
use App\Models\StatusHashtag;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\NetworkTimelineService;
use App\Services\StatusService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Source-aware invalidation
|--------------------------------------------------------------------------
|
| A cached member can enter a feed through more than one source: the viewer
| follows its author, the viewer follows one of its hashtags, or several of
| them at once. Invalidating one source must only evict a member when no
| other independent source still supplies it. These walks drive the real
| cleanup entry points - the account-unfollow job, the hashtag-unfollow job,
| profile-deletion cleanup, and the domain-removal job - against caches that
| mix self, followed-author, multi-tag, blocked, allowed-domain, malformed,
| and unresolvable members. They assert a member is only dropped when every
| supplying source is gone, that target-owned and unresolvable ids are pruned,
| that allowed and independently-sourced ids survive, and that a second
| cleanup run leaves the same ordered set.
|
*/

beforeEach(function () {
    config(['federation.network_timeline' => true]);
    config(['instance.hide_nsfw_on_public_feeds' => false]);
    Bus::fake();
    Redis::flushdb();
    Cache::flush();
});

afterEach(function () {
    Redis::flushdb();
    Cache::flush();
});

function sourceDisplayOrder(string $key): array
{
    return collect(Redis::zrevrange($key, 0, -1))
        ->map(fn ($v) => (string) $v)
        ->values()
        ->all();
}

/**
 * A local public photo authored by $authorPid with an explicit created_at so
 * the timeline score reflects publication time. Resolving it through
 * StatusService primes the cached payload the cleanup jobs read.
 */
function sourceLocalStatus(int $authorPid, Carbon $createdAt, array $tagNames = []): Status
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

    foreach ($tagNames as $name) {
        attachSourceTag($status, $authorPid, $name);
    }

    StatusService::get($status->id, false);

    return $status;
}

/**
 * A remote public photo whose uri (and therefore url) lives on $domain, so the
 * domain-removal job resolves its host to $domain.
 */
function sourceRemoteStatus(int $authorPid, string $domain, Carbon $createdAt): Status
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

    return $status;
}

/**
 * A remote boost whose wrapper lives on $domain and whose reblog target is a
 * status from the same blocked domain. The wrapper's own url resolves to the
 * blocked host, which is the member that should be pruned.
 */
function sourceRemoteBoostOfBlocked(int $boosterPid, string $domain, Status $original, Carbon $createdAt): Status
{
    $boost = Status::factory()->create([
        'profile_id' => $boosterPid,
        'type' => 'share',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => false,
        'uri' => 'https://'.$domain.'/p/'.bin2hex(random_bytes(6)),
        'in_reply_to_id' => null,
        'reblog_of_id' => $original->id,
        'is_nsfw' => false,
        'created_at' => $createdAt,
    ]);

    StatusService::get($boost->id, false);

    return $boost;
}

function attachSourceTag(Status $status, int $authorPid, string $name): Hashtag
{
    $hashtag = Hashtag::firstOrCreate(
        ['slug' => strtolower($name)],
        ['name' => $name]
    );

    StatusHashtag::create([
        'status_id' => $status->id,
        'hashtag_id' => $hashtag->id,
        'profile_id' => $authorPid,
        'status_visibility' => 'public',
    ]);

    return $hashtag;
}

it('retains a home member after author unfollow when a followed hashtag still supplies the member', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $author = User::factory()->create();
    $author->refresh();
    $authorPid = $author->profile_id;

    // The viewer follows the author and also follows the #sunset hashtag.
    Follower::create(['profile_id' => $pid, 'following_id' => $authorPid]);

    // Self post from the viewer, and an unrelated followed-author post with no
    // overlapping source - both must survive the unfollow untouched.
    $selfPost = sourceLocalStatus($pid, now()->subMinutes(40));
    $plainAuthorPost = sourceLocalStatus($authorPid, now()->subMinutes(30));

    // The contested member: authored by the account being unfollowed, but it
    // also carries #sunset, a hashtag the viewer independently follows. With
    // the hashtag source still live, the member should remain reachable.
    $hashtag = Hashtag::firstOrCreate(['slug' => 'sunset'], ['name' => 'sunset']);
    HashtagFollow::create([
        'user_id' => $viewer->id,
        'profile_id' => $pid,
        'hashtag_id' => $hashtag->id,
    ]);
    $dualSourced = sourceLocalStatus($authorPid, now()->subMinutes(20), ['sunset']);

    $key = HomeTimelineService::CACHE_KEY.$pid;
    foreach ([$selfPost, $plainAuthorPost, $dualSourced] as $s) {
        HomeTimelineService::add($pid, $s->id);
    }

    $before = [(string) $dualSourced->id, (string) $plainAuthorPost->id, (string) $selfPost->id];
    expect(sourceDisplayOrder($key))->toBe($before);

    (new FeedUnfollowPipeline($pid, $authorPid))->handle();

    $after = sourceDisplayOrder($key);

    // The self post is never a follow of the unfollowed author and stays.
    expect($after)->toContain((string) $selfPost->id);

    // The plain followed-author post has no other source and is correctly gone.
    expect($after)->not->toContain((string) $plainAuthorPost->id);

    // The contested member still has a live hashtag source, so it must remain.
    expect($after)->toContain((string) $dualSourced->id);

    // A second cleanup pass leaves the same ordered set.
    $snapshot = sourceDisplayOrder($key);
    (new FeedUnfollowPipeline($pid, $authorPid))->handle();
    expect(sourceDisplayOrder($key))->toBe($snapshot);
});

it('retains a home member after hashtag unfollow when another followed hashtag still supplies the member', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $stranger = User::factory()->create();
    $stranger->refresh();
    $strangerPid = $stranger->profile_id;

    // The viewer follows two hashtags, #sunset and #coffee, and follows no
    // accounts, so hashtag membership is the only source for stranger posts.
    $sunset = Hashtag::firstOrCreate(['slug' => 'sunset'], ['name' => 'sunset']);
    $coffee = Hashtag::firstOrCreate(['slug' => 'coffee'], ['name' => 'coffee']);
    foreach ([$sunset, $coffee] as $tag) {
        HashtagFollow::create([
            'user_id' => $viewer->id,
            'profile_id' => $pid,
            'hashtag_id' => $tag->id,
        ]);
    }

    // Single-tag member sourced only by #sunset - unfollowing #sunset leaves no
    // source, so it should be removed.
    $sunsetOnly = sourceLocalStatus($strangerPid, now()->subMinutes(30), ['sunset']);

    // Single-tag member sourced only by #coffee - unrelated to the #sunset
    // unfollow and must remain.
    $coffeeOnly = sourceLocalStatus($strangerPid, now()->subMinutes(20), ['coffee']);

    // Multi-tag member sourced by both #sunset and #coffee. Unfollowing #sunset
    // leaves #coffee as a live source, so the member must remain reachable.
    $dualTag = sourceLocalStatus($strangerPid, now()->subMinutes(10), ['sunset', 'coffee']);

    $key = HomeTimelineService::CACHE_KEY.$pid;
    foreach ([$sunsetOnly, $coffeeOnly, $dualTag] as $s) {
        HomeTimelineService::add($pid, $s->id);
    }

    $before = [(string) $dualTag->id, (string) $coffeeOnly->id, (string) $sunsetOnly->id];
    expect(sourceDisplayOrder($key))->toBe($before);

    (new HashtagUnfollowPipeline($sunset->id, $pid, 'sunset'))->handle();

    $after = sourceDisplayOrder($key);

    // The #sunset-only member loses its only source and is correctly removed.
    expect($after)->not->toContain((string) $sunsetOnly->id);

    // The #coffee-only member was never in scope of this unfollow.
    expect($after)->toContain((string) $coffeeOnly->id);

    // The multi-tag member still has a live #coffee source and must remain.
    expect($after)->toContain((string) $dualTag->id);

    // A second cleanup pass leaves the same ordered set.
    $snapshot = sourceDisplayOrder($key);
    (new HashtagUnfollowPipeline($sunset->id, $pid, 'sunset'))->handle();
    expect(sourceDisplayOrder($key))->toBe($snapshot);
});

it('deleteByProfileId removes target-owned and unresolved members while preserving unrelated live members', function () {
    $target = User::factory()->create();
    $target->refresh();
    $targetPid = $target->profile_id;

    $keeper = User::factory()->create();
    $keeper->refresh();
    $keeperPid = $keeper->profile_id;

    // Two members owned by the account being deleted and two live members owned
    // by an unrelated account that must survive in their exact display order.
    $targetOld = sourceLocalStatus($targetPid, now()->subMinutes(50));
    $targetNew = sourceLocalStatus($targetPid, now()->subMinutes(20));
    $keeperOld = sourceLocalStatus($keeperPid, now()->subMinutes(40));
    $keeperNew = sourceLocalStatus($keeperPid, now()->subMinutes(10));

    $key = NetworkTimelineService::CACHE_KEY;
    foreach ([$targetOld, $targetNew, $keeperOld, $keeperNew] as $s) {
        NetworkTimelineService::add($s->id);
    }

    // An id with no backing row at all - an unresolvable member seeded directly
    // above everything else. Cleanup must evict it regardless of ownership.
    $unresolvable = '9300000000000001';
    Redis::zadd($key, now()->addMinute()->timestamp, $unresolvable);

    $before = [
        $unresolvable,
        (string) $keeperNew->id,
        (string) $targetNew->id,
        (string) $keeperOld->id,
        (string) $targetOld->id,
    ];
    expect(sourceDisplayOrder($key))->toBe($before);

    NetworkTimelineService::deleteByProfileId($targetPid);

    $after = sourceDisplayOrder($key);

    // Both target-owned members are gone.
    expect($after)->not->toContain((string) $targetOld->id)
        ->and($after)->not->toContain((string) $targetNew->id);

    // The unresolvable member is pruned too.
    expect($after)->not->toContain($unresolvable);

    // The unrelated live members survive in their exact display order.
    expect($after)->toBe([(string) $keeperNew->id, (string) $keeperOld->id]);

    // A second cleanup pass leaves the same ordered set.
    NetworkTimelineService::deleteByProfileId($targetPid);
    expect(sourceDisplayOrder($key))->toBe([(string) $keeperNew->id, (string) $keeperOld->id]);
});

it('domain removal prunes blocked and unresolved members including boosts of blocked originals', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $blockedDomain = 'blocked.example';

    $blockedAuthor = Profile::factory()->create(['user_id' => null, 'domain' => $blockedDomain]);
    $allowedAuthor = Profile::factory()->create(['user_id' => null, 'domain' => 'allowed.example']);
    $boosterAuthor = Profile::factory()->create(['user_id' => null, 'domain' => $blockedDomain]);

    // A plain remote post on the blocked domain - must be pruned.
    $blockedPost = sourceRemoteStatus($blockedAuthor->id, $blockedDomain, now()->subMinutes(45));

    // A remote boost whose wrapper lives on the blocked domain and whose target
    // is a blocked-domain original - must be pruned.
    $blockedOriginal = sourceRemoteStatus($blockedAuthor->id, $blockedDomain, now()->subMinutes(60));
    $blockedBoost = sourceRemoteBoostOfBlocked($boosterAuthor->id, $blockedDomain, $blockedOriginal, now()->subMinutes(40));

    // A remote post on an unrelated allowed domain - must remain.
    $allowedPost = sourceRemoteStatus($allowedAuthor->id, 'allowed.example', now()->subMinutes(30));

    // A local post - its host is the instance domain and must remain.
    $localPost = sourceLocalStatus($pid, now()->subMinutes(20));

    $key = HomeTimelineService::CACHE_KEY.$pid;
    foreach ([$blockedPost, $blockedBoost, $allowedPost, $localPost] as $s) {
        HomeTimelineService::add($pid, $s->id);
    }

    // A malformed, unresolvable member seeded above everything. Domain removal
    // cannot resolve a host for it, so it must be pruned.
    $unresolvable = '9400000000000001';
    Redis::zadd($key, now()->addMinute()->timestamp, $unresolvable);

    $before = [
        $unresolvable,
        (string) $localPost->id,
        (string) $allowedPost->id,
        (string) $blockedBoost->id,
        (string) $blockedPost->id,
    ];
    expect(sourceDisplayOrder($key))->toBe($before);

    [$domainJob] = (new FeedRemoveDomainPipeline($pid, $blockedDomain))->withFakeBatch();
    $domainJob->handle();

    $after = sourceDisplayOrder($key);

    // Both blocked-domain members, plain and boost, are pruned.
    expect($after)->not->toContain((string) $blockedPost->id)
        ->and($after)->not->toContain((string) $blockedBoost->id);

    // The unresolvable member is pruned.
    expect($after)->not->toContain($unresolvable);

    // The allowed-domain and local members survive in exact display order.
    expect($after)->toBe([(string) $localPost->id, (string) $allowedPost->id]);

    // A second cleanup pass leaves the same ordered set.
    [$domainJobRepeat] = (new FeedRemoveDomainPipeline($pid, $blockedDomain))->withFakeBatch();
    $domainJobRepeat->handle();
    expect(sourceDisplayOrder($key))->toBe([(string) $localPost->id, (string) $allowedPost->id]);
});
