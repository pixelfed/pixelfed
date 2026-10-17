<?php

use App\Models\Follower;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Rounded snowflake cursor collisions
|--------------------------------------------------------------------------
|
| Web clients parse the 19-digit snowflake cursor through a JS Number, which
| rounds past 2^53, so the id sent back on scroll never matches a member
| exactly and several real ids collapse to the same float. These walks seed
| ids at and above 2^53 with deliberately non-monotonic publication scores,
| build an exact rounded-id collision plus two groups of ids that round to the
| same float, and prove that a rounded next cursor resumes at the display-order
| suffix after the member the client last saw: the newest entry is never
| replayed, every cached id is reached once across a full Link walk, and no
| collision diverts the cursor to a different member or repeats it.
|
*/

/**
 * 2^53. Consecutive integers from here collide under a JS Number cast, which
 * rounds to even: {base, base+1} -> base, {base+3, base+4, base+5} -> base+4.
 */
const COLLISION_BASE = 9007199254740992;

/**
 * Score plan keyed by id offset from the base. Scores are the publication
 * ranking (higher is newer) and are deliberately not monotonic with the id so
 * display order is driven by score, not by the numeric id.
 *
 * Buckets under a JS Number cast:
 *   +12            -> float +12  (newest, plain anchor)
 *   +9, +7         -> float +8   (non-exact group; the +8 anchor is omitted)
 *   +6             -> float +6   (plain)
 *   +5, +3         -> float +4   (non-exact group; the +4 anchor is omitted)
 *   +2             -> float +2   (exact rounded-id collision; member is present)
 *   +0             -> float +0   (plain, oldest)
 *
 * Each non-exact group's lower-scored member (+7 and +3) is the one a page
 * boundary lands on, and it is the lowest-scored member of its float bucket,
 * so the float resolver maps the rounded cursor back to that same member.
 */
function collisionScorePlan(): array
{
    return [
        12 => 1200,
        9 => 1100,
        7 => 1090,
        6 => 1080,
        5 => 1050,
        3 => 1040,
        2 => 1020,
        0 => 1000,
    ];
}

/**
 * Persist one status per planned offset with an id at or above the base, then
 * assign the exact publication scores directly to the sorted set so the float
 * boundaries are precise. Returns the ids keyed by offset.
 */
function seedCollisionFeed(string $cacheKey, callable $makeStatus): array
{
    Redis::del($cacheKey);

    $ids = [];
    foreach (collisionScorePlan() as $offset => $score) {
        $id = COLLISION_BASE + $offset;
        $makeStatus($id);
        $ids[$offset] = $id;
    }

    // Resolve first, then stamp the controlled score so the member survives
    // hydration and the ranking is exactly the plan rather than created_at.
    foreach (collisionScorePlan() as $offset => $score) {
        StatusService::get($ids[$offset], false);
        StatusService::getMastodon($ids[$offset], false);
        Redis::zadd($cacheKey, $score, $ids[$offset]);
    }

    return $ids;
}

function collisionDisplayOrder(string $cacheKey): array
{
    return collect(Redis::zrevrange($cacheKey, 0, -1))
        ->map(fn ($v) => (string) $v)
        ->values()
        ->all();
}

function parseNextCursor(?string $link): ?string
{
    if ($link && preg_match('/max_id=(\d+)>; rel="next"/', $link, $m)) {
        return $m[1];
    }

    return null;
}

function jsRound(string $id): string
{
    return (string) (int) (float) $id;
}

/**
 * Follow the rel="next" Link like the web client: round every returned cursor
 * through a JS Number before sending it back. Returns the ordered ids seen and
 * the ordered list of cursors issued.
 */
function walkCollisionFeed(callable $request, int $limit): array
{
    $seen = [];
    $cursors = [];
    $cursor = null;

    for ($page = 0; $page < 25; $page++) {
        $resp = $request($cursor)->assertOk();
        $ids = collect($resp->json())->pluck('id')->map(fn ($id) => (string) $id)->all();

        if (count($ids) === 0) {
            break;
        }

        foreach ($ids as $id) {
            $seen[] = $id;
        }

        $next = parseNextCursor($resp->headers->get('Link'));
        if (! $next) {
            break;
        }

        $rounded = jsRound($next);
        if (in_array($rounded, $cursors, true)) {
            // A repeated cursor means the walk is stuck; record it and stop so
            // the assertion below can fail loudly rather than loop forever.
            $cursors[] = $rounded;
            break;
        }
        $cursors[] = $rounded;
        $cursor = $rounded;
    }

    return [$seen, $cursors];
}

it('resumes the home feed after the displayed member when rounded snowflakes collide', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $author = User::factory()->create();
    $author->refresh();
    Follower::create(['profile_id' => $pid, 'following_id' => $author->profile_id]);

    $cacheKey = HomeTimelineService::CACHE_KEY.$pid;
    Cache::forget('pf:services:apiv1:home:cached:coldbootcheck:'.$pid);

    seedCollisionFeed($cacheKey, function ($id) use ($author) {
        Status::factory()->create([
            'id' => $id,
            'profile_id' => $author->profile_id,
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
            'in_reply_to_id' => null,
            'reblog_of_id' => null,
            'local' => true,
            'uri' => null,
        ]);
    });

    $order = collisionDisplayOrder($cacheKey);
    Passport::actingAs($viewer, ['read']);

    $limit = 3;

    // A rounded cursor at the lower member of the non-exact float group must
    // resume at the exact suffix after it, replaying nothing above it.
    $displayed = (string) (COLLISION_BASE + 3);
    $rank = array_search($displayed, $order, true);
    $expected = array_slice($order, $rank + 1, $limit);

    $res = $this->getJson('/api/v1/timelines/home?limit='.$limit.'&_pe=1&max_id='.jsRound($displayed))
        ->assertOk()
        ->json();
    $ids = collect($res)->pluck('id')->map(fn ($id) => (string) $id)->all();

    expect($ids)->toBe($expected);
    expect($ids)->not->toContain($order[0]);
    expect($ids)->not->toContain($displayed);

    [$seen, $cursors] = walkCollisionFeed(
        fn ($cursor) => $this->getJson(
            '/api/v1/timelines/home?limit='.$limit.'&_pe=1'.($cursor ? '&max_id='.$cursor : '')
        ),
        $limit
    );

    expect($seen)->toBe($order);
    expect(collect($seen)->unique()->count())->toBe(count($order));
    expect(collect($cursors)->unique()->count())->toBe(count($cursors));

    Redis::del($cacheKey);
});

it('resumes the local feed after the displayed member when rounded snowflakes collide', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    $cacheKey = PublicTimelineService::CACHE_KEY;
    Cache::put('api:v1:timelines:public:cache_check', 1, 3600);

    seedCollisionFeed($cacheKey, function ($id) {
        Status::factory()->create([
            'id' => $id,
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
            'in_reply_to_id' => null,
            'reblog_of_id' => null,
            'local' => true,
            'uri' => null,
        ]);
    });

    $order = collisionDisplayOrder($cacheKey);
    Passport::actingAs($viewer, ['read']);

    $limit = 3;

    $displayed = (string) (COLLISION_BASE + 3);
    $rank = array_search($displayed, $order, true);
    $expected = array_slice($order, $rank + 1, $limit);

    $res = $this->getJson('/api/v1/timelines/public?limit='.$limit.'&_pe=1&local=1&max_id='.jsRound($displayed))
        ->assertOk()
        ->json();
    $ids = collect($res)->pluck('id')->map(fn ($id) => (string) $id)->all();

    expect($ids)->toBe($expected);
    expect($ids)->not->toContain($order[0]);
    expect($ids)->not->toContain($displayed);

    [$seen, $cursors] = walkCollisionFeed(
        fn ($cursor) => $this->getJson(
            '/api/v1/timelines/public?limit='.$limit.'&_pe=1&local=1'.($cursor ? '&max_id='.$cursor : '')
        ),
        $limit
    );

    expect($seen)->toBe($order);
    expect(collect($seen)->unique()->count())->toBe(count($order));
    expect(collect($cursors)->unique()->count())->toBe(count($cursors));

    Redis::del($cacheKey);
});

it('resumes the network feed after the displayed member when rounded snowflakes collide', function () {
    config(['federation.network_timeline' => true]);
    config(['instance.hide_nsfw_on_public_feeds' => false]);

    $viewer = User::factory()->create();
    $viewer->refresh();

    $remoteAuthor = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);

    $cacheKey = NetworkTimelineService::CACHE_KEY;
    Cache::put('api:v1:timelines:network:cache_check', 1, 3600);

    seedCollisionFeed($cacheKey, function ($id) use ($remoteAuthor) {
        Status::factory()->create([
            'id' => $id,
            'profile_id' => $remoteAuthor->id,
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
            'in_reply_to_id' => null,
            'reblog_of_id' => null,
            'local' => false,
            'is_nsfw' => false,
            'uri' => 'https://remote.example/p/'.bin2hex(random_bytes(6)),
        ]);
    });

    $order = collisionDisplayOrder($cacheKey);
    Passport::actingAs($viewer, ['read']);

    $limit = 3;

    $displayed = (string) (COLLISION_BASE + 3);
    $rank = array_search($displayed, $order, true);
    $expected = array_slice($order, $rank + 1, $limit);

    $res = $this->getJson('/api/v1/timelines/public?limit='.$limit.'&_pe=1&remote=1&max_id='.jsRound($displayed))
        ->assertOk()
        ->json();
    $ids = collect($res)->pluck('id')->map(fn ($id) => (string) $id)->all();

    expect($ids)->toBe($expected);
    expect($ids)->not->toContain($order[0]);
    expect($ids)->not->toContain($displayed);

    [$seen, $cursors] = walkCollisionFeed(
        fn ($cursor) => $this->getJson(
            '/api/v1/timelines/public?limit='.$limit.'&_pe=1&remote=1'.($cursor ? '&max_id='.$cursor : '')
        ),
        $limit
    );

    expect($seen)->toBe($order);
    expect(collect($seen)->unique()->count())->toBe(count($order));
    expect(collect($cursors)->unique()->count())->toBe(count($cursors));

    Redis::del($cacheKey);
});
