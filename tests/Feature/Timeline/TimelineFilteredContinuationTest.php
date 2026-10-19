<?php

use App\Models\CustomFilter;
use App\Models\Follower;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Models\UserDomainBlock;
use App\Services\HomeTimelineService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use App\Services\UserFilterService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Filtered-window continuation
|--------------------------------------------------------------------------
|
| A bounded candidate window can hydrate to nothing once mutes, blocks,
| domain blocks and custom filters run, yet an older visible post still sits
| below it in display order. These walks follow the emitted rel="next" cursor
| across those dead windows, confirm the cursor tracks raw Redis display order
| (ZREVRANGE), reach every visible post exactly once in display order, never
| repeat a cursor, and stop only once the raw source is exhausted.
|
*/

const FILTERED_LOCAL_CHECK = 'api:v1:timelines:public:cache_check';
const FILTERED_NETWORK_CHECK = 'api:v1:timelines:network:cache_check';

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

function nextCursor(?string $link): ?string
{
    if (! $link) {
        return null;
    }

    if (preg_match('/max_id=(\d+)>; rel="next"/', $link, $m)) {
        return $m[1];
    }

    return null;
}

function hasNextRel(?string $link): bool
{
    return $link !== null && str_contains($link, 'rel="next"');
}

/**
 * The exact display order Redis serves the feed in, highest score first. Walk
 * oracles compare against this rather than numeric id order so a post with a
 * low id but a recent date lands where the client actually sees it.
 */
function displayOrder(string $key): array
{
    return collect(Redis::zrevrange($key, 0, -1))
        ->map(fn ($v) => (string) $v)
        ->values()
        ->all();
}

/**
 * A public photo with an explicit created_at so id order and date order can be
 * driven apart. Resolving it through StatusService primes the score the
 * timeline services read when adding it.
 */
function continuationStatus(int $authorPid, Carbon $createdAt, array $overrides = []): Status
{
    $status = Status::factory()->create(array_merge([
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
    ], $overrides));

    StatusService::get($status->id, false);
    StatusService::getMastodon($status->id, false);

    return $status;
}

it('follows the home next link past a fully filtered window with inverted id and date order', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $author = User::factory()->create();
    $author->refresh();
    Follower::create(['profile_id' => $pid, 'following_id' => $author->profile_id]);

    // Filtered posts are created first (lowest ids) in ascending date order, so
    // the oldest-dated filtered post holds the minimum raw id and sits at the
    // bottom of the displayed filtered block. The window overflows the home
    // padding (limit + 50), so the first raw page hydrates to nothing and the
    // empty-page cursor (minimum raw id) resolves to that boundary score.
    $filtered = collect(range(1, 56))->map(function ($i) use ($author) {
        return continuationStatus($author->profile_id, now()->subMinutes(200 - $i), [
            'caption' => 'hiddenkeyword drifting window '.$i,
            'rendered' => 'hiddenkeyword drifting window '.$i,
        ]);
    });

    // Visible posts are created last (highest ids) but carry the oldest dates,
    // so they sort to the very bottom of the feed. Id order and date order are
    // fully inverted: the visible posts are newest by id yet oldest by date.
    $visibleAbove = continuationStatus($author->profile_id, now()->subMinutes(400));
    $visibleBottom = continuationStatus($author->profile_id, now()->subMinutes(500));

    foreach ([...$filtered, $visibleAbove, $visibleBottom] as $status) {
        HomeTimelineService::add($pid, $status->id);
    }

    $filter = CustomFilter::create([
        'profile_id' => $pid,
        'title' => 'hide drift',
        'context' => ['home'],
        'action' => 1,
    ]);
    $filter->keywords()->create(['keyword' => 'hiddenkeyword', 'whole_word' => false]);
    Cache::forget('filters:v3:'.$pid);

    $key = HomeTimelineService::CACHE_KEY.$pid;
    $order = displayOrder($key);

    // Display order places both visible posts at the bottom, newer-dated first.
    expect(array_slice($order, -2))->toBe([(string) $visibleAbove->id, (string) $visibleBottom->id]);

    // Id order and date order are inverted: the visible posts carry the highest
    // ids, so the minimum raw id belongs to a filtered post.
    $minRawId = collect($order)->map(fn ($id) => (int) $id)->min();
    expect($minRawId)->not->toBe((int) $visibleBottom->id);
    expect($minRawId)->toBe((int) $filtered->first()->id);

    Passport::actingAs($viewer, ['read']);

    $walk = walkHomeFeed($this, '/api/v1/timelines/home?limit=2');

    // Both visible posts are reached, each exactly once, newer-dated first.
    $visibleSeen = collect($walk['seen'])
        ->filter(fn ($id) => in_array($id, [(string) $visibleAbove->id, (string) $visibleBottom->id]))
        ->values()
        ->all();
    expect($visibleSeen)->toBe([(string) $visibleAbove->id, (string) $visibleBottom->id]);

    // No post is served twice across the whole walk.
    expect($walk['seen'])->toBe(collect($walk['seen'])->unique()->values()->all());

    // Each emitted next cursor is a real member and the cursors never repeat.
    foreach ($walk['cursors'] as $c) {
        expect(Redis::zscore($key, $c))->not->toBeFalse();
    }
    expect($walk['cursors'])->toBe(collect($walk['cursors'])->unique()->values()->all());

    // Traversal ends on the raw source with at most one trailing empty page.
    expect($walk['trailingEmpty'])->toBeLessThanOrEqual(1);
});

it('continues the local timeline past more than limit plus five filtered entries', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    // A blocked account, a domain-blocked remote author and a muted account
    // each contribute hidden members, so the block, domain-block and mute paths
    // all consume the same spare window. Hidden members are interleaved one at a
    // time between visible posts, so the limit + 5 fetch always carries enough
    // survivors to keep emitting a cursor.
    $blocked = User::factory()->create();
    $blocked->refresh();
    $muted = User::factory()->create();
    $muted->refresh();
    $domainAuthor = Profile::factory()->create(['user_id' => null, 'domain' => 'blocked.example']);

    UserFilterService::block($viewer->profile_id, $blocked->profile_id);
    UserFilterService::mute($viewer->profile_id, $muted->profile_id);
    UserDomainBlock::create(['profile_id' => $viewer->profile_id, 'domain' => 'blocked.example']);
    UserFilterService::domainBlocks($viewer->profile_id, true);

    $visibleAuthor = User::factory()->create();
    $visibleAuthor->refresh();

    $visible = [];
    $minute = 1000;
    $hiddenTotal = 0;

    // Visible and hidden posts strictly alternate so any limit + 5 window holds
    // several survivors. Eight visible posts step down in date; a hidden member
    // sits just below each one.
    for ($v = 0; $v < 8; $v++) {
        $visible[] = continuationStatus($visibleAuthor->profile_id, now()->subMinutes($minute));
        $minute -= 5;

        $author = match ($v % 3) {
            0 => $blocked->profile_id,
            1 => $muted->profile_id,
            default => $domainAuthor->id,
        };
        $remote = $author === $domainAuthor->id;
        continuationStatus($author, now()->subMinutes($minute), [
            'local' => ! $remote,
            'uri' => $remote ? 'https://blocked.example/p/'.bin2hex(random_bytes(6)) : null,
        ]);
        $minute -= 1;
        $hiddenTotal++;
    }

    $all = Status::orderBy('created_at')->get();
    foreach ($all as $status) {
        PublicTimelineService::add($status->id);
    }
    Cache::put(FILTERED_LOCAL_CHECK, 1, 3600);

    // The feed carries more than limit + 5 hidden members overall.
    expect($hiddenTotal)->toBeGreaterThan(2 + 5);

    // Display order is the oracle: the visible posts in the exact order Redis
    // serves them, newest-dated first.
    $visibleIdSet = collect($visible)->pluck('id')->map(fn ($id) => (string) $id)->all();
    $expectedVisibleOrder = collect(displayOrder(PublicTimelineService::CACHE_KEY))
        ->filter(fn ($id) => in_array($id, $visibleIdSet, true))
        ->values()
        ->all();

    Passport::actingAs($viewer, ['read']);

    $walk = walkPublicFeed($this, '/api/v1/timelines/public?limit=2&local=1');

    // Every visible post is reached exactly once, in display order.
    expect($walk['served'])->toBe(collect($walk['served'])->unique()->values()->all());
    expect($walk['visible'])->toBe($expectedVisibleOrder);

    // Each emitted cursor is a real member of the set and cursors never repeat.
    foreach ($walk['cursors'] as $c) {
        expect(Redis::zscore(PublicTimelineService::CACHE_KEY, $c))->not->toBeFalse();
    }
    expect($walk['cursors'])->toBe(collect($walk['cursors'])->unique()->values()->all());
    expect($walk['trailingEmpty'])->toBeLessThanOrEqual(1);
    expect($walk['terminatedWithoutNext'])->toBeTrue();
});

it('continues the network timeline past more than limit plus five filtered entries', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    $blocked = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
    $domainAuthor = Profile::factory()->create(['user_id' => null, 'domain' => 'blocked.example']);
    $muted = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);

    UserFilterService::block($viewer->profile_id, $blocked->id);
    UserFilterService::mute($viewer->profile_id, $muted->id);
    UserDomainBlock::create(['profile_id' => $viewer->profile_id, 'domain' => 'blocked.example']);
    UserFilterService::domainBlocks($viewer->profile_id, true);

    $visibleAuthor = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);

    $visible = [];
    $minute = 1000;
    $hiddenTotal = 0;

    for ($v = 0; $v < 8; $v++) {
        $visible[] = continuationStatus($visibleAuthor->id, now()->subMinutes($minute), [
            'local' => false,
            'uri' => 'https://remote.example/p/'.bin2hex(random_bytes(6)),
        ]);
        $minute -= 5;

        $author = match ($v % 3) {
            0 => $blocked,
            1 => $muted,
            default => $domainAuthor,
        };
        $domain = $author === $domainAuthor ? 'blocked.example' : 'remote.example';
        continuationStatus($author->id, now()->subMinutes($minute), [
            'local' => false,
            'uri' => 'https://'.$domain.'/p/'.bin2hex(random_bytes(6)),
        ]);
        $minute -= 1;
        $hiddenTotal++;
    }

    $all = Status::orderBy('created_at')->get();
    foreach ($all as $status) {
        NetworkTimelineService::add($status->id);
    }
    Cache::put(FILTERED_NETWORK_CHECK, 1, 3600);

    expect($hiddenTotal)->toBeGreaterThan(2 + 5);

    $visibleIdSet = collect($visible)->pluck('id')->map(fn ($id) => (string) $id)->all();
    $expectedVisibleOrder = collect(displayOrder(NetworkTimelineService::CACHE_KEY))
        ->filter(fn ($id) => in_array($id, $visibleIdSet, true))
        ->values()
        ->all();

    Passport::actingAs($viewer, ['read']);

    $walk = walkPublicFeed($this, '/api/v1/timelines/public?limit=2&remote=1');

    expect($walk['served'])->toBe(collect($walk['served'])->unique()->values()->all());
    expect($walk['visible'])->toBe($expectedVisibleOrder);

    foreach ($walk['cursors'] as $c) {
        expect(Redis::zscore(NetworkTimelineService::CACHE_KEY, $c))->not->toBeFalse();
    }
    expect($walk['cursors'])->toBe(collect($walk['cursors'])->unique()->values()->all());
    expect($walk['trailingEmpty'])->toBeLessThanOrEqual(1);
    expect($walk['terminatedWithoutNext'])->toBeTrue();
});

it('omits the next link only after filtered source exhaustion', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $author = User::factory()->create();
    $author->refresh();
    Follower::create(['profile_id' => $pid, 'following_id' => $author->profile_id]);

    // A filtered block overflows the first padded window; two visible posts sit
    // at the very bottom in display order. The walk must reach them and then
    // drop rel="next" only once the raw source has nothing left below the last
    // served member.
    $filtered = collect(range(1, 54))->map(function ($i) use ($author) {
        return continuationStatus($author->profile_id, now()->subMinutes(200 - $i), [
            'caption' => 'blockme filtered run '.$i,
            'rendered' => 'blockme filtered run '.$i,
        ]);
    });

    $visibleNewer = continuationStatus($author->profile_id, now()->subMinutes(400));
    $visibleOldest = continuationStatus($author->profile_id, now()->subMinutes(500));

    foreach ([...$filtered, $visibleNewer, $visibleOldest] as $status) {
        HomeTimelineService::add($pid, $status->id);
    }

    $filter = CustomFilter::create([
        'profile_id' => $pid,
        'title' => 'hide run',
        'context' => ['home'],
        'action' => 1,
    ]);
    $filter->keywords()->create(['keyword' => 'blockme', 'whole_word' => false]);
    Cache::forget('filters:v3:'.$pid);

    $key = HomeTimelineService::CACHE_KEY.$pid;

    Passport::actingAs($viewer, ['read']);

    $walk = walkHomeFeed($this, '/api/v1/timelines/home?limit=2');

    // Both visible posts were served before the terminal page.
    expect($walk['seen'])->toContain((string) $visibleNewer->id)
        ->and($walk['seen'])->toContain((string) $visibleOldest->id);

    // The walk ended on a response that carried no next link.
    expect($walk['terminatedWithoutNext'])->toBeTrue();

    // The terminal state is genuine source exhaustion: nothing remains in Redis
    // below the oldest visible member.
    $oldestScore = (int) Redis::zscore($key, $visibleOldest->id);
    $membersBelow = Redis::zrangebyscore($key, '-inf', '('.$oldestScore);
    expect($membersBelow)->toBe([]);

    // Cursors never repeat and the walk needs at most one trailing empty page.
    expect($walk['cursors'])->toBe(collect($walk['cursors'])->unique()->values()->all());
    expect($walk['trailingEmpty'])->toBeLessThanOrEqual(1);
});

/**
 * Walk the home endpoint by following rel="next". Tracks every served id in
 * order, each emitted cursor, whether the walk ended on a response with no next
 * link, and the number of consecutive empty responses at the tail of the walk.
 */
function walkHomeFeed($test, string $base): array
{
    $cursor = null;
    $seen = [];
    $cursors = [];
    $trailingEmpty = 0;
    $terminatedWithoutNext = false;

    for ($page = 0; $page < 80; $page++) {
        $url = $cursor ? $base.'&max_id='.$cursor : $base;
        $resp = $test->getJson($url)->assertOk();
        $ids = collect($resp->json())->pluck('id')->map(fn ($id) => (string) $id)->all();

        $trailingEmpty = count($ids) === 0 ? $trailingEmpty + 1 : 0;

        foreach ($ids as $id) {
            $seen[] = $id;
        }

        $link = $resp->headers->get('Link');

        if (! hasNextRel($link)) {
            $terminatedWithoutNext = true;
            break;
        }

        $next = nextCursor($link);

        if ($next === null || $next === $cursor) {
            break;
        }

        $cursors[] = $next;
        $cursor = $next;
    }

    return [
        'seen' => $seen,
        'cursors' => $cursors,
        'trailingEmpty' => $trailingEmpty,
        'terminatedWithoutNext' => $terminatedWithoutNext,
    ];
}

/**
 * Walk a timelinePublic feed by following rel="next". Returns the visible ids
 * reached in display order, the cursors emitted, whether traversal ended on a
 * response with no next link, and the count of trailing empty responses.
 */
function walkPublicFeed($test, string $base): array
{
    $cursor = null;
    $served = [];
    $visible = [];
    $cursors = [];
    $trailingEmpty = 0;
    $terminatedWithoutNext = false;

    for ($page = 0; $page < 80; $page++) {
        $url = $cursor ? $base.'&max_id='.$cursor : $base;
        $resp = $test->getJson($url)->assertOk();
        $ids = collect($resp->json())->pluck('id')->map(fn ($id) => (string) $id)->all();

        $trailingEmpty = count($ids) === 0 ? $trailingEmpty + 1 : 0;

        foreach ($ids as $id) {
            $served[] = $id;
            if (! in_array($id, $visible, true)) {
                $visible[] = $id;
            }
        }

        $link = $resp->headers->get('Link');

        if (! hasNextRel($link)) {
            $terminatedWithoutNext = true;
            break;
        }

        $next = nextCursor($link);

        if ($next === null || $next === $cursor) {
            $terminatedWithoutNext = true;
            break;
        }

        $cursors[] = $next;
        $cursor = $next;
    }

    return [
        'served' => $served,
        'visible' => $visible,
        'cursors' => $cursors,
        'trailingEmpty' => $trailingEmpty,
        'terminatedWithoutNext' => $terminatedWithoutNext,
    ];
}
