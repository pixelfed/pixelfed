<?php

use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\FollowerService;
use App\Services\HomeTimelineService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use App\Services\SnowflakeService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

// Older posts past the cached window are pulled from the DB, written back without
// evicting the fresh window, and bounded so the key cannot grow without limit.

const HOME_CAP = 20;
const LOCAL_CAP = 20;
const NETWORK_CAP = 20;

// Floors pinned by the suite so seed ages are deterministic, not tied to defaults.
const HOME_FLOOR_DAYS = 180;
const LOCAL_FLOOR_DAYS = 90;
const NETWORK_FLOOR_HOURS = 1080; // 45 days

beforeEach(function () {
    config([
        'instance.timeline.home.cached' => true,
        'instance.timeline.local.cached' => true,
        'instance.timeline.network.cached' => true,
        'instance.timeline.home.cache_size' => HOME_CAP,
        'instance.timeline.local.cache_size' => LOCAL_CAP,
        'instance.timeline.network.cache_dropoff' => NETWORK_CAP,
        'instance.timeline.home.max_backfill_days' => HOME_FLOOR_DAYS,
        'instance.timeline.local.max_backfill_days' => LOCAL_FLOOR_DAYS,
        'instance.timeline.network.max_hours_old' => NETWORK_FLOOR_HOURS,
        'instance.hide_nsfw_on_public_feeds' => false,
    ]);

    Redis::del(PublicTimelineService::CACHE_KEY);
    Redis::del(NetworkTimelineService::CACHE_KEY);

    // Clear the cache_check memo so the controllers' warm-on-empty guard does not
    // leak between tests (we seed the ZSET ourselves; count>0 means it never warms,
    // but the memo value must not pin a stale decision).
    Cache::forget('api:v1:timelines:public:cache_check');
    Cache::forget('api:v1:timelines:network:cache_check');
});

afterEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
    Redis::del(NetworkTimelineService::CACHE_KEY);
});

// Snowflake id for a given time; add a per-row offset for distinct, ordered ids.
function idAt(Carbon $ts): int
{
    return (int) SnowflakeService::byDate($ts);
}

function toInts($ids): array
{
    return collect($ids)->map(fn ($i) => (int) $i)->values()->all();
}

// Plain photo posts only, so boosts do not enter into these assertions.

describe('home feed backfill', function () {
    // Seeds recent cached posts plus older DB-only posts above the floor.
    function seedHomeScenario(int $cachedCount = 5, int $olderCount = 4): array
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $viewer->refresh();
        $author->refresh();

        Redis::del(HomeTimelineService::CACHE_KEY.$viewer->profile_id);

        Follower::create([
            'profile_id' => $viewer->profile_id,
            'following_id' => $author->profile_id,
            'local_profile' => true,
        ]);
        FollowerService::add($viewer->profile_id, $author->profile_id);

        $cachedIds = [];
        for ($i = 0; $i < $cachedCount; $i++) {
            $id = idAt(now()->subDays(1)->subMinutes($i));
            Status::factory()->create([
                'id' => $id,
                'profile_id' => $author->profile_id,
                'type' => 'photo',
                'scope' => 'public',
                'visibility' => 'public',
                'local' => true,
                'in_reply_to_id' => null,
                'reblog_of_id' => null,
            ]);
            HomeTimelineService::add($viewer->profile_id, $id);
            $cachedIds[] = $id;
        }

        $olderIds = [];
        for ($i = 0; $i < $olderCount; $i++) {
            // ~5 days old: older than every cached id (1 day), far above the 180d floor.
            $id = idAt(now()->subDays(5)->subMinutes($i));
            Status::factory()->create([
                'id' => $id,
                'profile_id' => $author->profile_id,
                'type' => 'photo',
                'scope' => 'public',
                'visibility' => 'public',
                'local' => true,
                'in_reply_to_id' => null,
                'reblog_of_id' => null,
            ]);
            $olderIds[] = $id;
        }

        $following = FollowerService::getFollowingIds($viewer->profile_id);
        rsort($cachedIds);
        rsort($olderIds);

        return [$viewer, $following, $cachedIds, $olderIds, min($cachedIds)];
    }

    it('does not dead-end: max_id = oldest cached id returns the older rows in desc order', function () {
        [$viewer, $following, $cachedIds, $olderIds, $oldest] = seedHomeScenario();

        $returned = HomeTimelineService::backfillOlder($viewer->profile_id, $oldest, 30, $following);

        expect($returned)->toBe($olderIds);
        expect($returned)->toEqual(collect($returned)->sortDesc()->values()->all());
    });

    it('writes the backfilled ids into the ZSET', function () {
        [$viewer, $following, , $olderIds, $oldest] = seedHomeScenario();

        HomeTimelineService::backfillOlder($viewer->profile_id, $oldest, 30, $following);

        $members = toInts(HomeTimelineService::get($viewer->profile_id, 0, -1));
        foreach ($olderIds as $id) {
            expect($members)->toContain($id);
        }
    });

    it('a re-request is idempotent — ZSET count is unchanged on the second call', function () {
        [$viewer, $following, , , $oldest] = seedHomeScenario();

        HomeTimelineService::backfillOlder($viewer->profile_id, $oldest, 30, $following);
        $afterFirst = HomeTimelineService::count($viewer->profile_id);

        HomeTimelineService::backfillOlder($viewer->profile_id, $oldest, 30, $following);
        $afterSecond = HomeTimelineService::count($viewer->profile_id);

        expect($afterSecond)->toBe($afterFirst);
    });

    it('the just-backfilled older window survives the write-back (not evicted)', function () {
        [$viewer, $following, , $olderIds, $oldest] = seedHomeScenario();

        HomeTimelineService::backfillOlder($viewer->profile_id, $oldest, 30, $following);

        // The oldest member of the whole set must be the oldest backfilled id: if
        // evict:false were missed, zpopmin would have dropped exactly this tail.
        $members = toInts(HomeTimelineService::get($viewer->profile_id, 0, -1));
        expect(min($members))->toBe(min($olderIds));
        foreach ($olderIds as $id) {
            expect($members)->toContain($id);
        }
    });

    it('keeps the ZSET within cap + window', function () {
        // Seed a full cap of cached ids plus a window of older rows, then backfill a
        // full window so growth is pushed against the trimToBound ceiling.
        [$viewer, $following, , , $oldest] = seedHomeScenario(HOME_CAP, 15);
        $window = 15;

        HomeTimelineService::backfillOlder($viewer->profile_id, $oldest, $window, $following);

        expect(HomeTimelineService::count($viewer->profile_id))
            ->toBeLessThanOrEqual(HOME_CAP + $window);
    });

    it('a max_id below the depth floor returns end-of-feed and leaves the ZSET unchanged', function () {
        [$viewer, $following] = seedHomeScenario();

        $before = HomeTimelineService::count($viewer->profile_id);
        // Just past the pinned home floor.
        $belowFloor = idAt(now()->subDays(HOME_FLOOR_DAYS + 1));

        $returned = HomeTimelineService::backfillOlder($viewer->profile_id, $belowFloor, 30, $following);

        expect($returned)->toBe([]);
        expect(HomeTimelineService::count($viewer->profile_id))->toBe($before);
    });
});

describe('home feed backfill through the /api/v1/timelines/home endpoint', function () {
    it('serves older rows (no dead-end) when scrolling past the oldest cached id', function () {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $viewer->refresh();
        $author->refresh();

        Redis::del(HomeTimelineService::CACHE_KEY.$viewer->profile_id);
        Follower::create([
            'profile_id' => $viewer->profile_id,
            'following_id' => $author->profile_id,
            'local_profile' => true,
        ]);
        FollowerService::add($viewer->profile_id, $author->profile_id);

        $cached = idAt(now()->subDays(1));
        Status::factory()->create([
            'id' => $cached,
            'profile_id' => $author->profile_id,
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public', 'local' => true,
        ]);
        HomeTimelineService::add($viewer->profile_id, $cached);

        $older = idAt(now()->subDays(5));
        Status::factory()->create([
            'id' => $older,
            'profile_id' => $author->profile_id,
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public', 'local' => true,
        ]);

        Passport::actingAs($viewer, ['read']);
        $res = $this->getJson('/api/v1/timelines/home?max_id='.$cached.'&limit=10')->assertOk();

        $ids = collect($res->json())->pluck('id')->map(fn ($id) => (string) $id);
        expect($ids)->toContain((string) $older);
    });

    it('a re-request still serves the backfilled rows from cache', function () {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $viewer->refresh();
        $author->refresh();

        Redis::del(HomeTimelineService::CACHE_KEY.$viewer->profile_id);
        Follower::create([
            'profile_id' => $viewer->profile_id,
            'following_id' => $author->profile_id,
            'local_profile' => true,
        ]);
        FollowerService::add($viewer->profile_id, $author->profile_id);

        $cached = idAt(now()->subDays(1));
        Status::factory()->create([
            'id' => $cached,
            'profile_id' => $author->profile_id,
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public', 'local' => true,
        ]);
        HomeTimelineService::add($viewer->profile_id, $cached);

        $older = idAt(now()->subDays(5));
        Status::factory()->create([
            'id' => $older,
            'profile_id' => $author->profile_id,
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public', 'local' => true,
        ]);

        Passport::actingAs($viewer, ['read']);
        $this->getJson('/api/v1/timelines/home?max_id='.$cached.'&limit=10')->assertOk();

        // The older id is now a member; a second request serves it from the ZSET.
        $countAfterFirst = HomeTimelineService::count($viewer->profile_id);
        $res = $this->getJson('/api/v1/timelines/home?max_id='.$cached.'&limit=10')->assertOk();

        $ids = collect($res->json())->pluck('id')->map(fn ($id) => (string) $id);
        expect($ids)->toContain((string) $older);
        expect(HomeTimelineService::count($viewer->profile_id))->toBe($countAfterFirst);
    });
});

describe('public/local feed backfill', function () {
    // Seeds recent cached posts plus older DB-only posts above the floor.
    function seedPublicScenario(int $cachedCount = 5, int $olderCount = 4): array
    {
        $author = User::factory()->create();
        $author->refresh();

        $cachedIds = [];
        for ($i = 0; $i < $cachedCount; $i++) {
            $id = idAt(now()->subDays(1)->subMinutes($i));
            Status::factory()->create([
                'id' => $id,
                'profile_id' => $author->profile_id,
                'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
                'local' => true, 'uri' => null,
                'in_reply_to_id' => null, 'reblog_of_id' => null,
            ]);
            PublicTimelineService::add($id);
            $cachedIds[] = $id;
        }

        $olderIds = [];
        for ($i = 0; $i < $olderCount; $i++) {
            $id = idAt(now()->subDays(5)->subMinutes($i));
            Status::factory()->create([
                'id' => $id,
                'profile_id' => $author->profile_id,
                'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
                'local' => true, 'uri' => null,
                'in_reply_to_id' => null, 'reblog_of_id' => null,
            ]);
            $olderIds[] = $id;
        }

        rsort($cachedIds);
        rsort($olderIds);

        return [$author, $cachedIds, $olderIds, min($cachedIds)];
    }

    it('does not dead-end: returns the older rows in desc order', function () {
        [, , $olderIds, $oldest] = seedPublicScenario();

        $returned = PublicTimelineService::backfillOlder($oldest, 30);

        expect($returned)->toBe($olderIds);
    });

    it('writes the backfilled ids into the ZSET', function () {
        [, , $olderIds, $oldest] = seedPublicScenario();

        PublicTimelineService::backfillOlder($oldest, 30);

        $members = toInts(PublicTimelineService::get(0, -1));
        foreach ($olderIds as $id) {
            expect($members)->toContain($id);
        }
    });

    it('is idempotent — ZSET count unchanged on re-request', function () {
        [, , , $oldest] = seedPublicScenario();

        PublicTimelineService::backfillOlder($oldest, 30);
        $afterFirst = PublicTimelineService::count();

        PublicTimelineService::backfillOlder($oldest, 30);

        expect(PublicTimelineService::count())->toBe($afterFirst);
    });

    it('the just-backfilled older window survives the write-back', function () {
        [, , $olderIds, $oldest] = seedPublicScenario();

        PublicTimelineService::backfillOlder($oldest, 30);

        $members = toInts(PublicTimelineService::get(0, -1));
        expect(min($members))->toBe(min($olderIds));
    });

    it('keeps the ZSET within cap + window', function () {
        [, , , $oldest] = seedPublicScenario(LOCAL_CAP, 15);
        $window = 15;

        PublicTimelineService::backfillOlder($oldest, $window);

        expect(PublicTimelineService::count())->toBeLessThanOrEqual(LOCAL_CAP + $window);
    });

    it('a max_id below the floor returns end-of-feed, ZSET unchanged', function () {
        seedPublicScenario();

        $before = PublicTimelineService::count();
        $belowFloor = idAt(now()->subDays(LOCAL_FLOOR_DAYS + 1)); // just past the pinned local floor

        $returned = PublicTimelineService::backfillOlder($belowFloor, 30);

        expect($returned)->toBe([]);
        expect(PublicTimelineService::count())->toBe($before);
    });
});

describe('public/local feed backfill through both endpoints', function () {
    // Seeds recent cached posts plus older DB-only posts above the floor.
    function seedPublicEndpointScenario(): array
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $viewer->refresh();
        $author->refresh();

        $cached = idAt(now()->subDays(1));
        Status::factory()->create([
            'id' => $cached,
            'profile_id' => $author->profile_id,
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
            'local' => true, 'uri' => null,
        ]);
        PublicTimelineService::add($cached);

        $older = idAt(now()->subDays(5));
        Status::factory()->create([
            'id' => $older,
            'profile_id' => $author->profile_id,
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
            'local' => true, 'uri' => null,
        ]);

        return [$viewer, $cached, $older];
    }

    // /api/v1 uses Passport; /api/pixelfed/v1 uses the session guard — asserted in
    // separate tests so the two guards do not collide in one request lifecycle.

    it('ApiV1Controller::timelinePublic (local=1) serves older rows past the oldest cached id', function () {
        [$viewer, $cached, $older] = seedPublicEndpointScenario();

        Passport::actingAs($viewer, ['read']);
        $res = $this->getJson('/api/v1/timelines/public?local=1&max_id='.$cached.'&limit=20')->assertOk();

        $ids = collect($res->json())->pluck('id')->map(fn ($id) => (string) $id);
        expect($ids)->toContain((string) $older);
    });

    it('ApiV1Controller::timelinePublic re-request still serves the backfilled row from cache', function () {
        [$viewer, $cached, $older] = seedPublicEndpointScenario();

        Passport::actingAs($viewer, ['read']);
        $this->getJson('/api/v1/timelines/public?local=1&max_id='.$cached.'&limit=20')->assertOk();

        $countAfterFirst = PublicTimelineService::count();
        $res = $this->getJson('/api/v1/timelines/public?local=1&max_id='.$cached.'&limit=20')->assertOk();

        $ids = collect($res->json())->pluck('id')->map(fn ($id) => (string) $id);
        expect($ids)->toContain((string) $older);
        expect(PublicTimelineService::count())->toBe($countAfterFirst);
    });

    it('PublicApiController::publicTimelineApi serves older rows past the oldest cached id', function () {
        [$viewer, $cached, $older] = seedPublicEndpointScenario();

        $res = $this->actingAs($viewer)
            ->getJson('/api/pixelfed/v1/timelines/public?max_id='.$cached.'&limit=20')
            ->assertOk();

        $ids = collect($res->json())->pluck('id')->map(fn ($id) => (string) $id);
        expect($ids)->toContain((string) $older);
    });

    it('PublicApiController::publicTimelineApi re-request still serves the backfilled row', function () {
        [$viewer, $cached, $older] = seedPublicEndpointScenario();

        $this->actingAs($viewer)
            ->getJson('/api/pixelfed/v1/timelines/public?max_id='.$cached.'&limit=20')
            ->assertOk();

        $countAfterFirst = PublicTimelineService::count();
        $res = $this->actingAs($viewer)
            ->getJson('/api/pixelfed/v1/timelines/public?max_id='.$cached.'&limit=20')
            ->assertOk();

        $ids = collect($res->json())->pluck('id')->map(fn ($id) => (string) $id);
        expect($ids)->toContain((string) $older);
        expect(PublicTimelineService::count())->toBe($countAfterFirst);
    });
});

describe('network feed backfill', function () {
    // Seeds recent cached posts plus older DB-only posts above the floor.
    function seedNetworkScenario(int $cachedCount = 5, int $olderCount = 4): array
    {
        $author = User::factory()->create();
        $author->refresh();

        $cachedIds = [];
        for ($i = 0; $i < $cachedCount; $i++) {
            $id = idAt(now()->subDays(1)->subMinutes($i));
            Status::factory()->create([
                'id' => $id,
                'profile_id' => $author->profile_id,
                'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
                'local' => false,
                'uri' => 'https://remote.example/users/alice/statuses/'.$id,
                'in_reply_to_id' => null, 'reblog_of_id' => null,
            ]);
            NetworkTimelineService::add($id);
            $cachedIds[] = $id;
        }

        $olderIds = [];
        for ($i = 0; $i < $olderCount; $i++) {
            // Older than the cached window but inside the pinned network floor.
            $id = idAt(now()->subDays(30)->subMinutes($i));
            Status::factory()->create([
                'id' => $id,
                'profile_id' => $author->profile_id,
                'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
                'local' => false,
                'uri' => 'https://remote.example/users/alice/statuses/'.$id,
                'in_reply_to_id' => null, 'reblog_of_id' => null,
                'created_at' => now()->subDays(30),
            ]);
            $olderIds[] = $id;
        }

        rsort($cachedIds);
        rsort($olderIds);

        return [$author, $cachedIds, $olderIds, min($cachedIds)];
    }

    it('does not dead-end: returns the older remote rows in desc order', function () {
        [, , $olderIds, $oldest] = seedNetworkScenario();

        $returned = NetworkTimelineService::backfillOlder($oldest, 30);

        expect($returned)->toBe($olderIds);
    });

    it('writes the backfilled ids into the ZSET', function () {
        [, , $olderIds, $oldest] = seedNetworkScenario();

        NetworkTimelineService::backfillOlder($oldest, 30);

        $members = toInts(NetworkTimelineService::get(0, -1));
        foreach ($olderIds as $id) {
            expect($members)->toContain($id);
        }
    });

    it('is idempotent — ZSET count unchanged on re-request', function () {
        [, , , $oldest] = seedNetworkScenario();

        NetworkTimelineService::backfillOlder($oldest, 30);
        $afterFirst = NetworkTimelineService::count();

        NetworkTimelineService::backfillOlder($oldest, 30);

        expect(NetworkTimelineService::count())->toBe($afterFirst);
    });

    it('the just-backfilled older window survives the write-back', function () {
        [, , $olderIds, $oldest] = seedNetworkScenario();

        NetworkTimelineService::backfillOlder($oldest, 30);

        $members = toInts(NetworkTimelineService::get(0, -1));
        expect(min($members))->toBe(min($olderIds));
    });

    it('keeps the ZSET within cap + window', function () {
        [, , , $oldest] = seedNetworkScenario(NETWORK_CAP, 15);
        $window = 15;

        NetworkTimelineService::backfillOlder($oldest, $window);

        expect(NetworkTimelineService::count())->toBeLessThanOrEqual(NETWORK_CAP + $window);
    });

    it('a max_id below the floor returns end-of-feed, ZSET unchanged', function () {
        seedNetworkScenario();

        $before = NetworkTimelineService::count();
        // Just past the pinned network floor.
        $belowFloor = idAt(now()->subHours(NETWORK_FLOOR_HOURS + 1));

        $returned = NetworkTimelineService::backfillOlder($belowFloor, 30);

        expect($returned)->toBe([]);
        expect(NetworkTimelineService::count())->toBe($before);
    });
});

describe('network feed backfill through the /api/v1/timelines/public?remote=1 endpoint', function () {
    function seedNetworkEndpointScenario(): array
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $viewer->refresh();
        $author->refresh();

        $cached = idAt(now()->subDays(1));
        Status::factory()->create([
            'id' => $cached,
            'profile_id' => $author->profile_id,
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
            'local' => false,
            'uri' => 'https://remote.example/users/alice/statuses/'.$cached,
        ]);
        NetworkTimelineService::add($cached);

        $older = idAt(now()->subDays(30));
        Status::factory()->create([
            'id' => $older,
            'profile_id' => $author->profile_id,
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
            'local' => false,
            'uri' => 'https://remote.example/users/alice/statuses/'.$older,
            'created_at' => now()->subDays(30),
        ]);

        return [$viewer, $cached, $older];
    }

    it('serves older remote rows past the oldest cached id', function () {
        [$viewer, $cached, $older] = seedNetworkEndpointScenario();

        Passport::actingAs($viewer, ['read']);
        $res = $this->getJson('/api/v1/timelines/public?remote=1&max_id='.$cached.'&limit=20')->assertOk();

        $ids = collect($res->json())->pluck('id')->map(fn ($id) => (string) $id);
        expect($ids)->toContain((string) $older);
    });

    it('a re-request still serves the backfilled remote row from cache', function () {
        [$viewer, $cached, $older] = seedNetworkEndpointScenario();

        Passport::actingAs($viewer, ['read']);
        $this->getJson('/api/v1/timelines/public?remote=1&max_id='.$cached.'&limit=20')->assertOk();

        $countAfterFirst = NetworkTimelineService::count();
        $res = $this->getJson('/api/v1/timelines/public?remote=1&max_id='.$cached.'&limit=20')->assertOk();

        $ids = collect($res->json())->pluck('id')->map(fn ($id) => (string) $id);
        expect($ids)->toContain((string) $older);
        expect(NetworkTimelineService::count())->toBe($countAfterFirst);
    });
});
