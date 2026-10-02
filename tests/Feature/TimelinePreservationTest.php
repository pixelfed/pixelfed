<?php

use App\Jobs\HomeFeedPipeline\FeedWarmCachePipeline;
use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\FollowerService;
use App\Services\HomeTimelineService;
use App\Services\PublicTimelineService;
use App\Services\SnowflakeService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

// In-range reads come straight from the cache; only scrolling older touches the DB.
// An empty home feed warms once and returns 206; replies and DMs never appear.

const PRES_HOME_CAP = 50;
const PRES_LOCAL_CAP = 50;

beforeEach(function () {
    config([
        'instance.timeline.home.cached' => true,
        'instance.timeline.local.cached' => true,
        'instance.timeline.network.cached' => true,
        'instance.timeline.home.cache_size' => PRES_HOME_CAP,
        'instance.timeline.local.cache_size' => PRES_LOCAL_CAP,
        // Pin floors well past the seed ages so backfill always reaches them.
        'instance.timeline.home.max_backfill_days' => 180,
        'instance.timeline.local.max_backfill_days' => 90,
        'instance.hide_nsfw_on_public_feeds' => false,
    ]);

    Redis::del(PublicTimelineService::CACHE_KEY);
    Cache::forget('api:v1:timelines:public:cache_check');
});

afterEach(function () {
    Redis::del(PublicTimelineService::CACHE_KEY);
});

function presIdAt(Carbon $ts): int
{
    return (int) SnowflakeService::byDate($ts);
}

describe('in-range pagination is served from cache', function () {
    it('home: a first-page request returns cached ids and does not grow the ZSET', function () {
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

        // 8 recent cached posts; a first page of 5 is fully in range.
        $ids = [];
        for ($i = 0; $i < 8; $i++) {
            $id = presIdAt(now()->subDays(1)->subMinutes($i));
            Status::factory()->create([
                'id' => $id,
                'profile_id' => $author->profile_id,
                'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
                'local' => true, 'in_reply_to_id' => null, 'reblog_of_id' => null,
            ]);
            HomeTimelineService::add($viewer->profile_id, $id);
            $ids[] = $id;
        }
        rsort($ids);

        $before = HomeTimelineService::count($viewer->profile_id);

        Passport::actingAs($viewer, ['read']);
        $res = $this->getJson('/api/v1/timelines/home?limit=5')->assertOk();

        $returned = collect($res->json())->pluck('id')->map(fn ($id) => (string) $id);
        // First page = the newest cached ids.
        expect($returned)->toContain((string) $ids[0]);
        // No DB write-back on an in-range read: the ZSET is unchanged.
        expect(HomeTimelineService::count($viewer->profile_id))->toBe($before);
    });

    it('home: a max_id BETWEEN cached ids returns the next cached page without growing the ZSET', function () {
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

        $ids = [];
        for ($i = 0; $i < 10; $i++) {
            $id = presIdAt(now()->subDays(1)->subMinutes($i));
            Status::factory()->create([
                'id' => $id,
                'profile_id' => $author->profile_id,
                'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
                'local' => true, 'in_reply_to_id' => null, 'reblog_of_id' => null,
            ]);
            HomeTimelineService::add($viewer->profile_id, $id);
            $ids[] = $id;
        }
        rsort($ids); // desc

        // max_id = the 3rd-newest cached id → still well inside the cached set,
        // with 7 older cached ids below it, so a limit=5 page is filled from
        // cache alone and backfill never runs.
        $cursor = $ids[2];
        $expectedNext = $ids[3];

        $before = HomeTimelineService::count($viewer->profile_id);

        Passport::actingAs($viewer, ['read']);
        $res = $this->getJson('/api/v1/timelines/home?max_id='.$cursor.'&limit=5')->assertOk();

        $returned = collect($res->json())->pluck('id')->map(fn ($id) => (string) $id);
        expect($returned)->toContain((string) $expectedNext);
        expect($returned)->not->toContain((string) $cursor);
        expect(HomeTimelineService::count($viewer->profile_id))->toBe($before);
    });

    it('public/local: a first-page request returns cached ids and does not grow the ZSET', function () {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $viewer->refresh();
        $author->refresh();

        $ids = [];
        for ($i = 0; $i < 8; $i++) {
            $id = presIdAt(now()->subDays(1)->subMinutes($i));
            Status::factory()->create([
                'id' => $id,
                'profile_id' => $author->profile_id,
                'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
                'local' => true, 'uri' => null,
                'in_reply_to_id' => null, 'reblog_of_id' => null,
            ]);
            PublicTimelineService::add($id);
            $ids[] = $id;
        }
        rsort($ids);

        $before = PublicTimelineService::count();

        Passport::actingAs($viewer, ['read']);
        $res = $this->getJson('/api/v1/timelines/public?local=1&limit=5')->assertOk();

        $returned = collect($res->json())->pluck('id')->map(fn ($id) => (string) $id);
        expect($returned)->toContain((string) $ids[0]);
        expect(PublicTimelineService::count())->toBe($before);
    });

    it('public/local: a max_id BETWEEN cached ids returns the next cached page without growing the ZSET', function () {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $viewer->refresh();
        $author->refresh();

        $ids = [];
        for ($i = 0; $i < 10; $i++) {
            $id = presIdAt(now()->subDays(1)->subMinutes($i));
            Status::factory()->create([
                'id' => $id,
                'profile_id' => $author->profile_id,
                'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
                'local' => true, 'uri' => null,
                'in_reply_to_id' => null, 'reblog_of_id' => null,
            ]);
            PublicTimelineService::add($id);
            $ids[] = $id;
        }
        rsort($ids);

        $cursor = $ids[2];
        $expectedNext = $ids[3];

        $before = PublicTimelineService::count();

        Passport::actingAs($viewer, ['read']);
        $res = $this->getJson('/api/v1/timelines/public?local=1&max_id='.$cursor.'&limit=5')->assertOk();

        $returned = collect($res->json())->pluck('id')->map(fn ($id) => (string) $id);
        expect($returned)->toContain((string) $expectedNext);
        expect($returned)->not->toContain((string) $cursor);
        expect(PublicTimelineService::count())->toBe($before);
    });
});

describe('cold-boot warm and 206 for an empty home feed', function () {
    it('the first request on a cold feed returns 206 and dispatches the warm pipeline', function () {
        Queue::fake();

        $viewer = User::factory()->create();
        $viewer->refresh();

        Redis::del(HomeTimelineService::CACHE_KEY.$viewer->profile_id);
        Cache::forget('pf:services:apiv1:home:cached:coldbootcheck:'.$viewer->profile_id);

        // Genuinely cold: empty ZSET.
        expect((int) HomeTimelineService::count($viewer->profile_id))->toBe(0);

        Passport::actingAs($viewer, ['read']);
        $this->getJson('/api/v1/timelines/home?limit=10')
            ->assertStatus(206)
            ->assertJsonIsArray();

        Queue::assertPushed(FeedWarmCachePipeline::class);
    });

    it('a second cold request still returns 206 but does not re-dispatch the warm pipeline', function () {
        Queue::fake();

        $viewer = User::factory()->create();
        $viewer->refresh();

        Redis::del(HomeTimelineService::CACHE_KEY.$viewer->profile_id);
        Cache::forget('pf:services:apiv1:home:cached:coldbootcheck:'.$viewer->profile_id);

        Passport::actingAs($viewer, ['read']);

        // First hit warms the feed.
        $this->getJson('/api/v1/timelines/home?limit=10')->assertStatus(206);
        Queue::assertPushed(FeedWarmCachePipeline::class, 1);

        // Second hit while still cold: 206 again, but no second warm dispatch.
        $this->getJson('/api/v1/timelines/home?limit=10')->assertStatus(206);
        Queue::assertPushed(FeedWarmCachePipeline::class, 1);
    });

    it('a non-empty feed scrolled past its oldest id does NOT 206 (backfill path, not cold-boot)', function () {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $viewer->refresh();
        $author->refresh();

        Redis::del(HomeTimelineService::CACHE_KEY.$viewer->profile_id);
        Cache::forget('pf:services:apiv1:home:cached:coldbootcheck:'.$viewer->profile_id);
        Follower::create([
            'profile_id' => $viewer->profile_id,
            'following_id' => $author->profile_id,
            'local_profile' => true,
        ]);
        FollowerService::add($viewer->profile_id, $author->profile_id);

        // One cached post, one older DB-only post above the floor.
        $cached = presIdAt(now()->subDays(1));
        Status::factory()->create([
            'id' => $cached,
            'profile_id' => $author->profile_id,
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public', 'local' => true,
        ]);
        HomeTimelineService::add($viewer->profile_id, $cached);

        $older = presIdAt(now()->subDays(5));
        Status::factory()->create([
            'id' => $older,
            'profile_id' => $author->profile_id,
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public', 'local' => true,
        ]);

        Passport::actingAs($viewer, ['read']);
        // Scroll past the oldest cached id: non-empty ZSET → backfill, 200 not 206.
        $this->getJson('/api/v1/timelines/home?max_id='.$cached.'&limit=10')
            ->assertStatus(200);
    });
});

describe('replies and direct statuses are excluded from home and public', function () {
    it('home: a reply is never pulled into the feed by the backfill query', function () {
        // Replies are excluded by the query, so scrolling older serves the normal
        // post but never the reply.
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

        // One recent cached post to make the feed non-empty.
        $cached = presIdAt(now()->subDays(1));
        Status::factory()->create([
            'id' => $cached,
            'profile_id' => $author->profile_id,
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public', 'local' => true,
            'in_reply_to_id' => null, 'reblog_of_id' => null,
        ]);
        HomeTimelineService::add($viewer->profile_id, $cached);

        // Older DB-only normal post (should be backfilled) + older DB-only reply
        // (must be excluded by the backfill query).
        $olderPost = presIdAt(now()->subDays(5));
        Status::factory()->create([
            'id' => $olderPost,
            'profile_id' => $author->profile_id,
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public', 'local' => true,
            'in_reply_to_id' => null, 'reblog_of_id' => null,
        ]);

        $parentForReply = Status::factory()->create([
            'profile_id' => $author->profile_id,
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public', 'local' => true,
        ]);
        $olderReply = presIdAt(now()->subDays(5)->subMinutes(1));
        Status::factory()->create([
            'id' => $olderReply,
            'profile_id' => $author->profile_id,
            'type' => 'reply', 'scope' => 'public', 'visibility' => 'public', 'local' => true,
            'in_reply_to_id' => $parentForReply->id,
        ]);

        Passport::actingAs($viewer, ['read']);
        $res = $this->getJson('/api/v1/timelines/home?max_id='.$cached.'&limit=20')->assertOk();

        $returned = collect($res->json())->pluck('id')->map(fn ($id) => (string) $id);
        expect($returned)->toContain((string) $olderPost);
        expect($returned)->not->toContain((string) $olderReply);
    });

    it('public/local: a direct-scope status forced into the ZSET is absent from the response', function () {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $viewer->refresh();
        $author->refresh();

        $visible = Status::factory()->create([
            'profile_id' => $author->profile_id,
            'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
            'local' => true, 'uri' => null,
        ]);
        PublicTimelineService::add($visible->id);

        // A direct message, forced into the public ZSET.
        $direct = Status::factory()->create([
            'profile_id' => $author->profile_id,
            'type' => 'photo', 'scope' => 'direct', 'visibility' => 'direct',
            'local' => true, 'uri' => null,
        ]);
        PublicTimelineService::add($direct->id);

        Passport::actingAs($viewer, ['read']);
        $res = $this->getJson('/api/v1/timelines/public?local=1&limit=20')->assertOk();

        $returned = collect($res->json())->pluck('id')->map(fn ($id) => (string) $id);
        expect($returned)->toContain((string) $visible->id);
        expect($returned)->not->toContain((string) $direct->id);
    });
});
