<?php

use App\Models\Follower;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Equal-second pagination walks
|--------------------------------------------------------------------------
|
| Feeds are ranked by created_at at second resolution, and the ranked cursor
| uses an exclusive score bound. When a page ends inside a group of entries
| that share one second, the next request can skip the remaining members of
| that second. These walks seed a tied group, page through it with a limit
| that splits the group, and assert every entry surfaces exactly once in both
| cursor directions.
|
*/

function linkCursor(?string $link, string $rel): ?string
{
    if (! $link) {
        return null;
    }

    if (preg_match('/[?&](?:max_id|min_id)=(\d+)[^>]*>; rel="'.$rel.'"/', $link, $m)) {
        return $m[1];
    }

    return null;
}

function redisDisplayOrder(string $key): array
{
    return collect(Redis::zrevrange($key, 0, -1))
        ->map(fn ($v) => (string) $v)
        ->values()
        ->all();
}

function pageIds($res): array
{
    return collect($res)->pluck('id')->map(fn ($id) => (string) $id)->all();
}

/**
 * Build a feed of persisted statuses where the middle block shares one
 * second-resolution timestamp and the surrounding entries sit on their own
 * distinct seconds. Returns the created statuses newest-first by intended
 * display order.
 */
function seedTiedStatuses(callable $factory): array
{
    $tiedMoment = Carbon::parse('2023-06-01 12:00:00');

    // Two entries strictly newer than the tie, four tied in one second, then
    // one strictly older. Seven total so a limit that splits the tie still has
    // content on both sides.
    $newer = [
        $factory($tiedMoment->copy()->addSeconds(40)),
        $factory($tiedMoment->copy()->addSeconds(20)),
    ];

    $tied = [
        $factory($tiedMoment->copy()),
        $factory($tiedMoment->copy()),
        $factory($tiedMoment->copy()),
        $factory($tiedMoment->copy()),
    ];

    $older = [
        $factory($tiedMoment->copy()->subSeconds(20)),
    ];

    return array_merge($newer, $tied, $older);
}

/**
 * Follow the rel="next" cursor from the first page to exhaustion and assert
 * the walk reproduces the Redis display order with no repeats and a bounded
 * page size.
 */
function assertMaxIdWalkMatchesDisplayOrder(
    Closure $request,
    array $displayOrder,
    int $limit
): void {
    $seen = [];
    $cursors = [];
    $maxId = null;

    for ($page = 0; $page < 40; $page++) {
        $resp = $request($maxId, null)->assertOk();
        $ids = pageIds($resp->json());

        expect(count($ids))->toBeLessThanOrEqual($limit);

        if (count($ids) === 0) {
            break;
        }

        foreach ($ids as $id) {
            expect($seen)->not->toContain($id);
            $seen[] = $id;
        }

        $next = linkCursor($resp->headers->get('Link'), 'next');

        if (! $next || $next === $maxId) {
            break;
        }

        expect($cursors)->not->toContain($next);
        $cursors[] = $next;
        $maxId = $next;
    }

    expect($seen)->toBe($displayOrder);
    expect(collect($seen)->unique()->count())->toBe(count($displayOrder));
}

it('walks every same-second home status exactly once in both cursor directions', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $author = User::factory()->create();
    $author->refresh();
    Follower::create(['profile_id' => $pid, 'following_id' => $author->profile_id]);

    $factory = function (Carbon $at) use ($author) {
        $status = Status::factory()->create([
            'profile_id' => $author->profile_id,
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
            'in_reply_to_id' => null,
            'reblog_of_id' => null,
            'local' => true,
            'uri' => null,
            'created_at' => $at,
        ]);
        StatusService::get($status->id, false);
        StatusService::getMastodon($status->id, false);

        return $status;
    };

    $statuses = seedTiedStatuses($factory);

    $key = HomeTimelineService::CACHE_KEY.$pid;
    Redis::del($key);
    Cache::forget('pf:services:apiv1:home:cached:coldbootcheck:'.$pid);

    foreach ($statuses as $status) {
        HomeTimelineService::add($pid, $status->id);
    }

    $displayOrder = redisDisplayOrder($key);
    expect(count($displayOrder))->toBe(count($statuses));

    Passport::actingAs($viewer, ['read']);

    $limit = 2;

    assertMaxIdWalkMatchesDisplayOrder(
        function ($maxId, $minId) use ($limit) {
            $url = '/api/v1/timelines/home?_pe=1&limit='.$limit;
            if ($maxId) {
                $url .= '&max_id='.$maxId;
            }

            return $this->getJson($url);
        },
        $displayOrder,
        $limit
    );

    // A tied member dropped from page one must still surface later: the walk
    // above already proved the full set appears, so confirm the first page did
    // not contain the whole tied group.
    $firstPage = pageIds($this->getJson('/api/v1/timelines/home?_pe=1&limit='.$limit)->json());
    expect(count($firstPage))->toBe($limit);

    // A min_id cursor planted inside the tie group returns the exact newer
    // display-order window: everything ranked above the cursor, newest-first.
    $tiedCursor = $displayOrder[3];
    $cursorRank = array_search($tiedCursor, $displayOrder, true);
    $expectedNewer = array_slice($displayOrder, 0, $cursorRank);

    $minRes = pageIds(
        $this->getJson('/api/v1/timelines/home?_pe=1&limit=10&min_id='.$tiedCursor)->json()
    );

    expect($minRes)->toBe($expectedNewer);
    expect($minRes)->not->toContain($tiedCursor);

    Redis::del($key);
});

it('walks every same-second local status exactly once in both cursor directions', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();

    $author = User::factory()->create();
    $author->refresh();

    $factory = function (Carbon $at) use ($author) {
        $status = Status::factory()->create([
            'profile_id' => $author->profile_id,
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
            'in_reply_to_id' => null,
            'reblog_of_id' => null,
            'local' => true,
            'uri' => null,
            'created_at' => $at,
        ]);
        StatusService::get($status->id, false);
        StatusService::getMastodon($status->id, false);

        return $status;
    };

    $key = PublicTimelineService::CACHE_KEY;
    Redis::del($key);
    Cache::forget('api:v1:timelines:public:cache_check');

    $statuses = seedTiedStatuses($factory);

    foreach ($statuses as $status) {
        PublicTimelineService::add($status->id);
    }

    $displayOrder = redisDisplayOrder($key);
    expect(count($displayOrder))->toBe(count($statuses));

    Passport::actingAs($viewer, ['read']);

    $limit = 2;

    assertMaxIdWalkMatchesDisplayOrder(
        function ($maxId, $minId) use ($limit) {
            $url = '/api/v1/timelines/public?_pe=1&local=1&limit='.$limit;
            if ($maxId) {
                $url .= '&max_id='.$maxId;
            }

            return $this->getJson($url);
        },
        $displayOrder,
        $limit
    );

    $firstPage = pageIds(
        $this->getJson('/api/v1/timelines/public?_pe=1&local=1&limit='.$limit)->json()
    );
    expect(count($firstPage))->toBe($limit);

    $tiedCursor = $displayOrder[3];
    $cursorRank = array_search($tiedCursor, $displayOrder, true);
    $expectedNewer = array_slice($displayOrder, 0, $cursorRank);

    $minRes = pageIds(
        $this->getJson('/api/v1/timelines/public?_pe=1&local=1&limit=10&min_id='.$tiedCursor)->json()
    );

    expect($minRes)->toBe($expectedNewer);
    expect($minRes)->not->toContain($tiedCursor);

    Redis::del($key);
});

it('walks every same-second network status exactly once in both cursor directions', function () {
    config(['federation.network_timeline' => true]);
    config(['instance.hide_nsfw_on_public_feeds' => false]);

    $viewer = User::factory()->create();
    $viewer->refresh();

    $author = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);

    $factory = function (Carbon $at) use ($author) {
        $status = Status::factory()->create([
            'profile_id' => $author->id,
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
            'in_reply_to_id' => null,
            'reblog_of_id' => null,
            'local' => false,
            'is_nsfw' => false,
            'uri' => 'https://remote.example/p/'.bin2hex(random_bytes(6)),
            'created_at' => $at,
        ]);
        StatusService::get($status->id, false);
        StatusService::getMastodon($status->id, false);

        return $status;
    };

    $key = NetworkTimelineService::CACHE_KEY;
    Redis::del($key);
    Cache::forget('api:v1:timelines:network:cache_check');

    $statuses = seedTiedStatuses($factory);

    foreach ($statuses as $status) {
        NetworkTimelineService::add($status->id);
    }

    $displayOrder = redisDisplayOrder($key);
    expect(count($displayOrder))->toBe(count($statuses));

    Passport::actingAs($viewer, ['read']);

    $limit = 2;

    assertMaxIdWalkMatchesDisplayOrder(
        function ($maxId, $minId) use ($limit) {
            $url = '/api/v1/timelines/public?_pe=1&remote=1&limit='.$limit;
            if ($maxId) {
                $url .= '&max_id='.$maxId;
            }

            return $this->getJson($url);
        },
        $displayOrder,
        $limit
    );

    $firstPage = pageIds(
        $this->getJson('/api/v1/timelines/public?_pe=1&remote=1&limit='.$limit)->json()
    );
    expect(count($firstPage))->toBe($limit);

    $tiedCursor = $displayOrder[3];
    $cursorRank = array_search($tiedCursor, $displayOrder, true);
    $expectedNewer = array_slice($displayOrder, 0, $cursorRank);

    $minRes = pageIds(
        $this->getJson('/api/v1/timelines/public?_pe=1&remote=1&limit=10&min_id='.$tiedCursor)->json()
    );

    expect($minRes)->toBe($expectedNewer);
    expect($minRes)->not->toContain($tiedCursor);

    Redis::del($key);
});
