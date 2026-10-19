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
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

afterEach(function () {
    Redis::flushdb();
});

function rebuildHomeStatus(int $profileId): Status
{
    return Status::factory()->create([
        'profile_id' => $profileId,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'uri' => null,
    ]);
}

it('force-rebuilds a stale home feed from the database', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $author = User::factory()->create();
    $author->refresh();
    Follower::create(['profile_id' => $pid, 'following_id' => $author->profile_id]);

    $real = rebuildHomeStatus($author->profile_id);

    // Seed the cache with a stale id that no longer reflects the DB.
    HomeTimelineService::add($pid, 999999999);
    expect(collect(HomeTimelineService::get($pid, 0, -1))->map(fn ($v) => (int) $v)->all())
        ->toContain(999999999)
        ->and(collect(HomeTimelineService::get($pid, 0, -1))->map(fn ($v) => (int) $v)->all())
        ->not->toContain((int) $real->id);

    $this->artisan('app:home-feed-rebuild', ['id' => $pid, '--sync' => true, '--force' => true])
        ->assertExitCode(0);

    $cached = collect(HomeTimelineService::get($pid, 0, -1))->map(fn ($v) => (int) $v)->all();

    expect($cached)->toContain((int) $real->id)
        ->and($cached)->not->toContain(999999999);
});

it('reports counts without changing anything in dry-run', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    HomeTimelineService::add($pid, 123);

    $this->artisan('app:home-feed-rebuild', ['id' => $pid, '--dry-run' => true])
        ->assertExitCode(0);

    expect(collect(HomeTimelineService::get($pid, 0, -1))->map(fn ($v) => (int) $v)->all())
        ->toBe([123]);
});

function rebuildLocalStatus(): Status
{
    return Status::factory()->create([
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => true,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'uri' => null,
    ]);
}

it('rebuilds the instance-wide local timeline with --local', function () {
    config(['instance.hide_nsfw_on_public_feeds' => false]);
    Redis::del(PublicTimelineService::CACHE_KEY);

    $real = rebuildLocalStatus();
    StatusService::get($real->id);

    PublicTimelineService::add(999999999);

    $this->artisan('app:home-feed-rebuild', ['--local' => true, '--force' => true])
        ->assertExitCode(0);

    $cached = collect(PublicTimelineService::get(0, -1))->map(fn ($v) => (int) $v)->all();

    expect($cached)->toContain((int) $real->id)
        ->and($cached)->not->toContain(999999999);
});

it('rebuilds the instance-wide network timeline with --network', function () {
    config(['instance.hide_nsfw_on_public_feeds' => false]);
    config(['instance.timeline.network.max_hours_old' => 2160]);
    Redis::del(NetworkTimelineService::CACHE_KEY);

    $author = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
    $real = Status::factory()->create([
        'profile_id' => $author->id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => false,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'is_nsfw' => false,
        'uri' => 'https://remote.example/p/'.uniqid(),
        'created_at' => now()->subMinutes(5),
    ]);
    StatusService::get($real->id);

    NetworkTimelineService::add(999999999);

    $this->artisan('app:home-feed-rebuild', ['--network' => true, '--force' => true])
        ->assertExitCode(0);

    $cached = collect(NetworkTimelineService::get(0, -1))->map(fn ($v) => (int) $v)->all();

    expect($cached)->toContain((int) $real->id)
        ->and($cached)->not->toContain(999999999);
});

it('errors when no target is given', function () {
    $this->artisan('app:home-feed-rebuild')
        ->assertExitCode(1);
});
