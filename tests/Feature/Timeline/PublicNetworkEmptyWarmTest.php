<?php

use App\Models\Status;
use App\Models\User;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Repeated warms of an instance-wide feed that has nothing to show
|--------------------------------------------------------------------------
|
| An instance with no eligible posts yet leaves both instance-wide feeds at
| zero members, and zero members is the only signal the read path has, so
| every single request re-runs the full candidate query. The outcome has to
| be recorded so the query runs once per interval, and any write to the feed
| has to put the warm back in play.
|
*/

beforeEach(function () {
    config(['instance.hide_nsfw_on_public_feeds' => false]);
    Redis::del(PublicTimelineService::CACHE_KEY);
    Redis::del(NetworkTimelineService::CACHE_KEY);
});

afterEach(function () {
    Redis::flushdb();
});

function emptyWarmStatusQueries(callable $fn): int
{
    $count = 0;

    DB::listen(function ($query) use (&$count) {
        if (preg_match('/from\s+"?statuses"?/i', $query->sql)) {
            $count++;
        }
    });

    $fn();

    return $count;
}

function emptyWarmViewer(): User
{
    $viewer = User::factory()->create();
    $viewer->refresh();

    Passport::actingAs($viewer, ['read']);

    return $viewer;
}

it('queries for local timeline candidates once while the instance has none', function () {
    emptyWarmViewer();

    $first = emptyWarmStatusQueries(function () {
        $this->getJson('/api/v1/timelines/public?limit=10')->assertOk();
    });

    expect($first)->toBeGreaterThan(0);

    $second = emptyWarmStatusQueries(function () {
        $this->getJson('/api/v1/timelines/public?limit=10')->assertOk();
    });

    expect($second)->toBe(0);
});

it('queries for network timeline candidates once while the instance has none', function () {
    emptyWarmViewer();

    $first = emptyWarmStatusQueries(function () {
        $this->getJson('/api/v1/timelines/public?remote=1&limit=10')->assertOk();
    });

    expect($first)->toBeGreaterThan(0);

    $second = emptyWarmStatusQueries(function () {
        $this->getJson('/api/v1/timelines/public?remote=1&limit=10')->assertOk();
    });

    expect($second)->toBe(0);
});

it('records an empty warm on both instance-wide feeds', function () {
    PublicTimelineService::warmCache(true, 400);
    NetworkTimelineService::warmCache(true);

    expect(PublicTimelineService::shouldWarmEmpty())->toBeFalse()
        ->and(NetworkTimelineService::shouldWarmEmpty())->toBeFalse();
});

it('puts the warm back in play when something is written to the feed', function () {
    PublicTimelineService::warmCache(true, 400);
    NetworkTimelineService::warmCache(true);

    $status = Status::factory()->create([
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => true,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'uri' => null,
    ]);
    StatusService::get($status->id);

    PublicTimelineService::add($status->id);
    NetworkTimelineService::add($status->id);

    expect(PublicTimelineService::shouldWarmEmpty())->toBeTrue()
        ->and(NetworkTimelineService::shouldWarmEmpty())->toBeTrue();
});

it('still clears the local timeline set when a warm finds nothing', function () {
    PublicTimelineService::add(999999999);

    PublicTimelineService::warmCache(true, 400);

    expect(PublicTimelineService::count())->toBe(0);
});
