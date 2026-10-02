<?php

use App\Models\CustomFilter;
use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\FollowerService;
use App\Services\HomeTimelineService;
use App\Services\PublicTimelineService;
use App\Services\SnowflakeService;
use App\Services\UserFilterService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

// A hidden status must stay hidden whether its id was already cached or pulled
// in from the DB when scrolling older — both paths run the same filter chain.

const CACHED_AGE_DAYS = 1;   // recent — seeded straight into the ZSET

const OLDER_AGE_DAYS = 5;    // older — DB only, above every floor, below cached

beforeEach(function () {
    config([
        'instance.timeline.home.cached' => true,
        'instance.timeline.local.cached' => true,
        'instance.timeline.network.cached' => true,
        // Pin floors well past the seed ages so backfill always reaches them.
        'instance.timeline.home.max_backfill_days' => 180,
        'instance.timeline.local.max_backfill_days' => 90,
    ]);

    Redis::del(PublicTimelineService::CACHE_KEY);
    Cache::forget('api:v1:timelines:public:cache_check');
});

afterEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
});

function parityIdAt(Carbon $ts): int
{
    return (int) SnowflakeService::byDate($ts);
}

// Reset the memoized custom-filter limit statics so per-test config takes effect.
function resetParityFilterLimitStatics(): void
{
    $ref = new ReflectionClass(CustomFilter::class);
    foreach (['maxFiltersPerUser', 'maxKeywordsPerFilter', 'maxContentScanLimit', 'maxPatternLength'] as $prop) {
        if ($ref->hasProperty($prop)) {
            $p = $ref->getProperty($prop);
            $p->setAccessible(true);
            $p->setValue(null, null);
        }
    }
}

// Create a hide-action keyword filter for the given profile and context.
function makeHideFilter(int $pid, string $keyword, string $context): void
{
    resetParityFilterLimitStatics();
    $filter = CustomFilter::create([
        'profile_id' => $pid,
        'phrase' => 'parity-'.$keyword,
        'context' => [$context],
        'action' => CustomFilter::ACTION_HIDE,
    ]);
    $filter->keywords()->create(['keyword' => $keyword, 'whole_word' => false]);

    Cache::forget("filters:v3:{$pid}");
}

function pluckIds($res): array
{
    return collect($res->json())->pluck('id')->map(fn ($id) => (string) $id)->all();
}

describe('home feed: custom-filter (hide) parity', function () {
    // Seeds a visible post and a hidden one, the hidden either cached or older.
    function seedHomeFilterScenario(string $keyword, bool $hiddenOlder): array
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

        $visibleId = parityIdAt(now()->subDays(CACHED_AGE_DAYS));
        Status::factory()->create([
            'id' => $visibleId,
            'profile_id' => $author->profile_id,
            'caption' => 'a perfectly ordinary post',
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
            'local' => true, 'in_reply_to_id' => null, 'reblog_of_id' => null,
        ]);
        HomeTimelineService::add($viewer->profile_id, $visibleId);

        $hiddenAge = $hiddenOlder ? OLDER_AGE_DAYS : CACHED_AGE_DAYS;
        // one extra second so cached-path ids never collide with the visible id
        $hiddenId = parityIdAt(now()->subDays($hiddenAge)->subSeconds(30));
        Status::factory()->create([
            'id' => $hiddenId,
            'profile_id' => $author->profile_id,
            'caption' => 'this mentions '.$keyword.' in the body',
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
            'local' => true, 'in_reply_to_id' => null, 'reblog_of_id' => null,
        ]);

        if (! $hiddenOlder) {
            // cached-id path: the hidden id is already a ZSET member
            HomeTimelineService::add($viewer->profile_id, $hiddenId);
        }

        return [$viewer, $visibleId, $hiddenId];
    }

    it('(cached-id path) excludes a hidden post already in the ZSET', function () {
        $keyword = 'zzcachedhome';
        [$viewer, $visibleId, $hiddenId] = seedHomeFilterScenario($keyword, hiddenOlder: false);
        makeHideFilter($viewer->profile_id, $keyword, 'home');

        Passport::actingAs($viewer, ['read']);
        $res = $this->getJson('/api/v1/timelines/home?limit=20')->assertOk();

        $ids = pluckIds($res);
        expect($ids)->toContain((string) $visibleId);
        expect($ids)->not->toContain((string) $hiddenId);
    });

    it('(backfilled-id path) excludes a hidden older post surfaced by backfill', function () {
        $keyword = 'zzbackfillhome';
        [$viewer, $visibleId, $hiddenId] = seedHomeFilterScenario($keyword, hiddenOlder: true);
        makeHideFilter($viewer->profile_id, $keyword, 'home');

        Passport::actingAs($viewer, ['read']);
        // scroll past the oldest cached id (= the visible id) so backfill runs
        $res = $this->getJson('/api/v1/timelines/home?max_id='.$visibleId.'&limit=20')->assertOk();

        $ids = pluckIds($res);
        // backfill surfaced the hidden id, but the shared filter chain drops it
        expect($ids)->not->toContain((string) $hiddenId);
    });
});

describe('public/local feed: custom-filter (hide) parity', function () {
    // Seeds a visible post and a hidden one, the hidden either cached or older.
    function seedPublicFilterScenario(string $keyword, bool $hiddenOlder): array
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $viewer->refresh();
        $author->refresh();

        $visibleId = parityIdAt(now()->subDays(CACHED_AGE_DAYS));
        Status::factory()->create([
            'id' => $visibleId,
            'profile_id' => $author->profile_id,
            'caption' => 'a perfectly ordinary post',
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
            'local' => true, 'uri' => null,
            'in_reply_to_id' => null, 'reblog_of_id' => null,
        ]);
        PublicTimelineService::add($visibleId);

        $hiddenAge = $hiddenOlder ? OLDER_AGE_DAYS : CACHED_AGE_DAYS;
        $hiddenId = parityIdAt(now()->subDays($hiddenAge)->subSeconds(30));
        Status::factory()->create([
            'id' => $hiddenId,
            'profile_id' => $author->profile_id,
            'caption' => 'this mentions '.$keyword.' in the body',
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
            'local' => true, 'uri' => null,
            'in_reply_to_id' => null, 'reblog_of_id' => null,
        ]);

        if (! $hiddenOlder) {
            PublicTimelineService::add($hiddenId);
        }

        return [$viewer, $visibleId, $hiddenId];
    }

    it('(cached-id path) excludes a hidden post already in the ZSET', function () {
        $keyword = 'zzcachedpublic';
        [$viewer, $visibleId, $hiddenId] = seedPublicFilterScenario($keyword, hiddenOlder: false);
        makeHideFilter($viewer->profile_id, $keyword, 'public');

        Passport::actingAs($viewer, ['read']);
        $res = $this->getJson('/api/v1/timelines/public?local=1&limit=20')->assertOk();

        $ids = pluckIds($res);
        expect($ids)->toContain((string) $visibleId);
        expect($ids)->not->toContain((string) $hiddenId);
    });

    it('(backfilled-id path) excludes a hidden older post surfaced by backfill', function () {
        $keyword = 'zzbackfillpublic';
        [$viewer, $visibleId, $hiddenId] = seedPublicFilterScenario($keyword, hiddenOlder: true);
        makeHideFilter($viewer->profile_id, $keyword, 'public');

        Passport::actingAs($viewer, ['read']);
        $res = $this->getJson('/api/v1/timelines/public?local=1&max_id='.$visibleId.'&limit=20')->assertOk();

        $ids = pluckIds($res);
        expect($ids)->not->toContain((string) $hiddenId);
    });
});

describe('public/local feed: mute parity', function () {
    // Seeds a visible post and a hidden one, the hidden either cached or older.
    function seedPublicMuteScenario(bool $hiddenOlder): array
    {
        $viewer = User::factory()->create();
        $visibleAuthor = User::factory()->create();
        $mutedAuthor = User::factory()->create();
        $viewer->refresh();
        $visibleAuthor->refresh();
        $mutedAuthor->refresh();

        $visibleId = parityIdAt(now()->subDays(CACHED_AGE_DAYS));
        Status::factory()->create([
            'id' => $visibleId,
            'profile_id' => $visibleAuthor->profile_id,
            'caption' => 'a visible post',
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
            'local' => true, 'uri' => null,
            'in_reply_to_id' => null, 'reblog_of_id' => null,
        ]);
        PublicTimelineService::add($visibleId);

        $hiddenAge = $hiddenOlder ? OLDER_AGE_DAYS : CACHED_AGE_DAYS;
        $mutedPostId = parityIdAt(now()->subDays($hiddenAge)->subSeconds(30));
        Status::factory()->create([
            'id' => $mutedPostId,
            'profile_id' => $mutedAuthor->profile_id,
            'caption' => 'a muted authors post',
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
            'local' => true, 'uri' => null,
            'in_reply_to_id' => null, 'reblog_of_id' => null,
        ]);

        if (! $hiddenOlder) {
            PublicTimelineService::add($mutedPostId);
        }

        UserFilterService::mute($viewer->profile_id, $mutedAuthor->profile_id);

        return [$viewer, $visibleId, $mutedPostId];
    }

    it('(cached-id path) excludes a muted authors post already in the ZSET', function () {
        [$viewer, $visibleId, $mutedPostId] = seedPublicMuteScenario(hiddenOlder: false);

        Passport::actingAs($viewer, ['read']);
        $res = $this->getJson('/api/v1/timelines/public?local=1&limit=20')->assertOk();

        $ids = pluckIds($res);
        expect($ids)->toContain((string) $visibleId);
        expect($ids)->not->toContain((string) $mutedPostId);
    });

    it('(backfilled-id path) excludes a muted authors older post surfaced by backfill', function () {
        [$viewer, $visibleId, $mutedPostId] = seedPublicMuteScenario(hiddenOlder: true);

        Passport::actingAs($viewer, ['read']);
        $res = $this->getJson('/api/v1/timelines/public?local=1&max_id='.$visibleId.'&limit=20')->assertOk();

        $ids = pluckIds($res);
        expect($ids)->not->toContain((string) $mutedPostId);
    });
});

describe('public/local feed: NSFW hide parity', function () {
    it('(backfilled-id path) does not surface a hidden NSFW older row', function () {
        config(['instance.hide_nsfw_on_public_feeds' => true]);

        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $viewer->refresh();
        $author->refresh();

        $visibleId = parityIdAt(now()->subDays(CACHED_AGE_DAYS));
        Status::factory()->create([
            'id' => $visibleId,
            'profile_id' => $author->profile_id,
            'caption' => 'a visible sfw post',
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
            'local' => true, 'uri' => null, 'is_nsfw' => false,
            'in_reply_to_id' => null, 'reblog_of_id' => null,
        ]);
        PublicTimelineService::add($visibleId);

        $nsfwId = parityIdAt(now()->subDays(OLDER_AGE_DAYS)->subSeconds(30));
        Status::factory()->create([
            'id' => $nsfwId,
            'profile_id' => $author->profile_id,
            'caption' => 'an nsfw older post',
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
            'local' => true, 'uri' => null, 'is_nsfw' => true,
            'in_reply_to_id' => null, 'reblog_of_id' => null,
        ]);

        Passport::actingAs($viewer, ['read']);
        $res = $this->getJson('/api/v1/timelines/public?local=1&max_id='.$visibleId.'&limit=20')->assertOk();

        $ids = pluckIds($res);
        // nsfw rows are filtered in the query, so they are never surfaced
        expect($ids)->not->toContain((string) $nsfwId);
    });
});

// Muted authors are dropped during hydrate, so cached and backfilled ids are both filtered.

describe('home feed: mute parity', function () {
    function seedHomeMuteScenario(bool $hiddenOlder): array
    {
        $viewer = User::factory()->create();
        $visibleAuthor = User::factory()->create();
        $mutedAuthor = User::factory()->create();
        $viewer->refresh();
        $visibleAuthor->refresh();
        $mutedAuthor->refresh();

        Redis::del(HomeTimelineService::CACHE_KEY.$viewer->profile_id);

        foreach ([$visibleAuthor, $mutedAuthor] as $a) {
            Follower::create([
                'profile_id' => $viewer->profile_id,
                'following_id' => $a->profile_id,
                'local_profile' => true,
            ]);
            FollowerService::add($viewer->profile_id, $a->profile_id);
        }

        $visibleId = parityIdAt(now()->subDays(CACHED_AGE_DAYS));
        Status::factory()->create([
            'id' => $visibleId,
            'profile_id' => $visibleAuthor->profile_id,
            'caption' => 'a visible post',
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
            'local' => true, 'in_reply_to_id' => null, 'reblog_of_id' => null,
        ]);
        HomeTimelineService::add($viewer->profile_id, $visibleId);

        $hiddenAge = $hiddenOlder ? OLDER_AGE_DAYS : CACHED_AGE_DAYS;
        $mutedPostId = parityIdAt(now()->subDays($hiddenAge)->subSeconds(30));
        Status::factory()->create([
            'id' => $mutedPostId,
            'profile_id' => $mutedAuthor->profile_id,
            'caption' => 'a muted authors post',
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
            'local' => true, 'in_reply_to_id' => null, 'reblog_of_id' => null,
        ]);

        if (! $hiddenOlder) {
            HomeTimelineService::add($viewer->profile_id, $mutedPostId);
        }

        UserFilterService::mute($viewer->profile_id, $mutedAuthor->profile_id);

        return [$viewer, $visibleId, $mutedPostId];
    }

    it('(backfilled-id path) excludes a muted authors older post', function () {
        [$viewer, $visibleId, $mutedPostId] = seedHomeMuteScenario(hiddenOlder: true);

        Passport::actingAs($viewer, ['read']);
        $res = $this->getJson('/api/v1/timelines/home?max_id='.$visibleId.'&limit=20')->assertOk();

        expect(pluckIds($res))->not->toContain((string) $mutedPostId);
    });

    it('(cached-id path) excludes a muted authors pre-cached post', function () {
        [$viewer, $visibleId, $mutedPostId] = seedHomeMuteScenario(hiddenOlder: false);

        Passport::actingAs($viewer, ['read']);
        $res = $this->getJson('/api/v1/timelines/home?limit=20')->assertOk();

        $ids = pluckIds($res);
        expect($ids)->toContain((string) $visibleId);
        expect($ids)->not->toContain((string) $mutedPostId);
    });
});
