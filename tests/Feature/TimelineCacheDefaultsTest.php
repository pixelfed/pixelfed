<?php

use App\Jobs\HomeFeedPipeline\FeedInsertPipeline;
use App\Jobs\HomeFeedPipeline\FeedRemovePipeline;
use App\Jobs\HomeFeedPipeline\FeedRemoveRemotePipeline;
use App\Jobs\StatusPipeline\StatusEntityLexer;
use App\Models\Status;
use App\Models\User;
use App\Services\PublicTimelineService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

// Post delivery and removal run unconditionally, and both public endpoints decide
// caching from instance.timeline.local.cached.

describe('home fanout', function () {
    it('dispatches FeedInsertPipeline for an eligible local post', function () {
        Queue::fake();

        expect(config('exp.cached_home_timeline'))->toBeNull();

        $user = User::factory()->create();
        $user->refresh();

        $status = Status::factory()->create([
            'profile_id' => $user->profile_id,
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
            'in_reply_to_id' => null,
        ]);

        (new StatusEntityLexer($status))->handle();

        Queue::assertPushed(FeedInsertPipeline::class);
    });
});

describe('delete removal dispatch', function () {
    it('dispatches FeedRemovePipeline for a local status delete', function () {
        $status = Status::factory()->create([
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
            'uri' => null,
        ]);

        Queue::fake();

        $status->delete();

        Queue::assertPushed(FeedRemovePipeline::class);
        Queue::assertNotPushed(FeedRemoveRemotePipeline::class);
    });

    it('dispatches FeedRemoveRemotePipeline for a remote status delete', function () {
        $status = Status::factory()->create([
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
            'uri' => 'https://remote.example/users/alice/statuses/1',
        ]);

        Queue::fake();

        $status->delete();

        Queue::assertPushed(FeedRemoveRemotePipeline::class);
        Queue::assertNotPushed(FeedRemovePipeline::class);
    });
});

describe('both public endpoints agree from instance.timeline.local.cached', function () {
    beforeEach(function () {
        Redis::del(PublicTimelineService::CACHE_KEY);
    });

    // The two endpoints use different auth guards, so they are asserted separately.

    it('ApiV1Controller::timelinePublic (local) honors local.cached when true', function () {
        config(['instance.timeline.local.cached' => true]);

        $user = User::factory()->create();
        $user->refresh();

        $status = Status::factory()->create([
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
            'local' => true,
        ]);

        // Seed the ZSET so the cached read returns the known id.
        PublicTimelineService::add($status->id);

        Passport::actingAs($user, ['read']);

        $res = $this->getJson('/api/v1/timelines/public?local=1&limit=20')->assertOk();
        $ids = collect($res->json())->pluck('id')->map(fn ($id) => (string) $id);

        expect($ids)->toContain((string) $status->id);
    });

    it('PublicApiController::publicTimelineApi honors local.cached when true', function () {
        config(['instance.timeline.local.cached' => true]);

        $user = User::factory()->create();
        $user->refresh();

        $status = Status::factory()->create([
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
            'local' => true,
        ]);

        PublicTimelineService::add($status->id);

        $res = $this->actingAs($user)
            ->getJson('/api/pixelfed/v1/timelines/public?limit=20')
            ->assertOk();
        $ids = collect($res->json())->pluck('id')->map(fn ($id) => (string) $id);

        expect($ids)->toContain((string) $status->id);
    });

    it('ApiV1Controller::timelinePublic (local) serves from the DB when local.cached is false', function () {
        config(['instance.timeline.local.cached' => false]);

        $user = User::factory()->create();
        $user->refresh();

        $status = Status::factory()->create([
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
            'local' => true,
        ]);

        Passport::actingAs($user, ['read']);

        $res = $this->getJson('/api/v1/timelines/public?local=1&limit=20')->assertOk();
        $ids = collect($res->json())->pluck('id')->map(fn ($id) => (string) $id);

        expect($ids)->toContain((string) $status->id);
    });

    it('PublicApiController::publicTimelineApi serves from the DB when local.cached is false', function () {
        config(['instance.timeline.local.cached' => false]);

        $user = User::factory()->create();
        $user->refresh();

        $status = Status::factory()->create([
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
            'local' => true,
        ]);

        $res = $this->actingAs($user)
            ->getJson('/api/pixelfed/v1/timelines/public?limit=20')
            ->assertOk();
        $ids = collect($res->json())->pluck('id')->map(fn ($id) => (string) $id);

        expect($ids)->toContain((string) $status->id);
    });
});

describe('timeline config', function () {
    it('does not define the legacy cached timeline flags', function () {
        expect(config('exp.cached_home_timeline'))->toBeNull();
        expect(config('exp.cached_public_timeline'))->toBeNull();
    });

    it('keeps the other experimental flags resolvable', function () {
        expect(config('exp.polls'))->not->toBeNull();
        expect(config('exp.gps'))->not->toBeNull();
        expect(config('exp.top'))->not->toBeNull();
        expect(config('exp.spa'))->not->toBeNull();
        expect(config('exp.emc'))->not->toBeNull();
        expect(config('exp.hls'))->not->toBeNull();
        expect(config('exp.autolink'))->not->toBeNull();
    });

    it('exposes the new timeline config keys', function () {
        expect(config('instance.timeline.home.cache_size'))->not->toBeNull();
        expect(config('instance.timeline.home.max_backfill_days'))->not->toBeNull();
        expect(config('instance.timeline.local.cache_size'))->not->toBeNull();
        expect(config('instance.timeline.local.max_backfill_days'))->not->toBeNull();
        expect(config('instance.timeline.network.cache_dropoff'))->not->toBeNull();
    });
});
