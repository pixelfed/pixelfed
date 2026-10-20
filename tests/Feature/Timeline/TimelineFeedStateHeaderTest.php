<?php

use App\Models\Follower;
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
| Reporting why a timeline response is empty
|--------------------------------------------------------------------------
|
| An empty array is three different answers: the feed is being rebuilt and
| will have content shortly, the feed genuinely has nothing in it, or the
| client has reached the end of what there is. A client that cannot tell
| them apart renders the onboarding screen over a feed that is one second
| away from being ready, which is what users are reporting.
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

function feedStatePhoto(int $profileId): Status
{
    $status = Status::factory()->create([
        'profile_id' => $profileId,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => true,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'uri' => null,
        'created_at' => now()->subMinutes(10),
    ]);

    StatusService::get($status->id, false);
    StatusService::getMastodon($status->id, false);

    return $status;
}

/** A viewer whose home feed holds one renderable post. */
function feedStateReadyViewer(): User
{
    $viewer = User::factory()->create();
    $viewer->refresh();

    $author = User::factory()->create();
    $author->refresh();
    Follower::withoutEvents(fn () => Follower::create([
        'profile_id' => $viewer->profile_id,
        'following_id' => $author->profile_id,
    ]));

    HomeTimelineService::add($viewer->profile_id, feedStatePhoto($author->profile_id)->id);

    Passport::actingAs($viewer, ['read']);

    return $viewer;
}

/** A viewer with a cold feed whose warm lock is already held elsewhere. */
function feedStateRebuildingViewer(): array
{
    $viewer = User::factory()->create();
    $viewer->refresh();

    $author = User::factory()->create();
    $author->refresh();
    Follower::withoutEvents(fn () => Follower::create([
        'profile_id' => $viewer->profile_id,
        'following_id' => $author->profile_id,
    ]));
    feedStatePhoto($author->profile_id);

    Redis::del(HomeTimelineService::CACHE_KEY.$viewer->profile_id);

    $lock = Cache::lock('pf:timeline:home:warm:lock:'.$viewer->profile_id, 30);
    $lock->get();

    Passport::actingAs($viewer, ['read']);

    return [$viewer, $lock];
}

/** A viewer who follows nobody, after a warm has recorded the feed as empty. */
function feedStateEmptyViewer(): User
{
    $viewer = User::factory()->create();
    $viewer->refresh();

    HomeTimelineService::warmCache($viewer->profile_id, true, 100);

    Passport::actingAs($viewer, ['read']);

    return $viewer;
}

it('reports a ready home feed', function () {
    feedStateReadyViewer();

    $res = $this->getJson('/api/v1/timelines/home?limit=10')->assertOk();

    expect($res->headers->get('X-Pixelfed-Feed-State'))->toBe('ready')
        ->and($res->headers->get('Retry-After'))->toBeNull();
});

it('reports a home feed that is being rebuilt elsewhere', function () {
    [, $lock] = feedStateRebuildingViewer();

    $res = $this->getJson('/api/v1/timelines/home?limit=10')->assertOk();

    expect($res->headers->get('X-Pixelfed-Feed-State'))->toBe('rebuilding')
        ->and($res->headers->get('Retry-After'))->toBe('5');

    $lock->release();
});

it('reports a home feed that genuinely has nothing in it', function () {
    feedStateEmptyViewer();

    $res = $this->getJson('/api/v1/timelines/home?limit=10')->assertOk();

    expect($res->headers->get('X-Pixelfed-Feed-State'))->toBe('empty')
        ->and($res->headers->get('Retry-After'))->toBeNull();
});

it('answers a pixelfed client with 206 while the home feed is rebuilding', function () {
    [, $lock] = feedStateRebuildingViewer();

    $this->getJson('/api/v1/timelines/home?limit=10&_pe=1')->assertStatus(206);
    $this->getJson('/api/v1/timelines/home?limit=10')->assertStatus(200);

    $lock->release();
});

it('keeps 200 for a ready or empty home feed whatever the client asks for', function () {
    feedStateReadyViewer();
    $this->getJson('/api/v1/timelines/home?limit=10&_pe=1')->assertStatus(200);

    feedStateEmptyViewer();
    $this->getJson('/api/v1/timelines/home?limit=10&_pe=1')->assertStatus(200);
});

it('lets an operator turn the rebuilding status back to 200', function () {
    config(['instance.timeline.rebuilding_status' => 200]);

    [, $lock] = feedStateRebuildingViewer();

    $this->getJson('/api/v1/timelines/home?limit=10&_pe=1')->assertStatus(200);

    $lock->release();
});

it('falls back to 206 when the configured rebuilding status is not usable', function () {
    config(['instance.timeline.rebuilding_status' => 999]);

    [, $lock] = feedStateRebuildingViewer();

    $this->getJson('/api/v1/timelines/home?limit=10&_pe=1')->assertStatus(206);

    $lock->release();
});

it('exposes the feed state headers to cross-origin clients', function () {
    expect(config('cors.exposed_headers'))->toContain('X-Pixelfed-Feed-State')
        ->and(config('cors.exposed_headers'))->toContain('Retry-After');
});

it('reports a rebuilding local timeline without changing its status', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    Passport::actingAs($viewer, ['read']);

    $lock = Cache::lock('api:v1:timelines:public:cache_check:lock', 10);
    $lock->get();

    $res = $this->getJson('/api/v1/timelines/public?limit=10&_pe=1')->assertStatus(200);

    expect($res->headers->get('X-Pixelfed-Feed-State'))->toBe('rebuilding')
        ->and($res->headers->get('Retry-After'))->toBe('5');

    $lock->release();
});

it('reports a rebuilding network timeline without changing its status', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    Passport::actingAs($viewer, ['read']);

    $lock = Cache::lock('api:v1:timelines:network:cache_check:lock', 10);
    $lock->get();

    $res = $this->getJson('/api/v1/timelines/public?remote=1&limit=10&_pe=1')->assertStatus(200);

    expect($res->headers->get('X-Pixelfed-Feed-State'))->toBe('rebuilding')
        ->and($res->headers->get('Retry-After'))->toBe('5');

    $lock->release();
});
