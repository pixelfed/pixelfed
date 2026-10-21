<?php

use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\NetworkTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| The network timeline trim cap
|--------------------------------------------------------------------------
|
| INSTANCE_NETWORK_TIMELINE_CACHE_DROPOFF is read straight into the trim
| cap, so an operator who leaves it blank gets a cap of zero: every insert
| trims the set back to nothing and the network timeline is permanently
| empty. The same value bounds the warm's candidate query, where a blank
| silently removes the bound instead.
|
*/

beforeEach(function () {
    config(['instance.hide_nsfw_on_public_feeds' => false]);
    config(['instance.timeline.network.cache_dropoff' => '']);
    Redis::del(NetworkTimelineService::CACHE_KEY);
});

afterEach(function () {
    Redis::flushdb();
});

function trimCapRemoteStatus(?Profile $author = null): Status
{
    static $seq = 0;

    $author ??= Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);

    $status = Status::factory()->create([
        'profile_id' => $author->id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => false,
        'in_reply_to_id' => null,
        'reblog_of_id' => null,
        'is_nsfw' => false,
        'uri' => 'https://remote.example/p/'.uniqid(),
        'created_at' => now()->subMinutes(60 - ($seq++)),
    ]);

    StatusService::get($status->id);
    StatusService::getMastodon($status->id);

    return $status;
}

function trimCapCandidateSql(callable $fn): array
{
    $sql = [];

    DB::listen(function ($query) use (&$sql) {
        if (preg_match('/from\s+"?statuses"?/i', $query->sql)) {
            $sql[] = $query->sql;
        }
    });

    $fn();

    return $sql;
}

it('keeps an inserted member when the configured cap is blank', function () {
    $status = trimCapRemoteStatus();

    NetworkTimelineService::add($status->id);

    expect(NetworkTimelineService::count())->toBe(1);
});

it('serves a network page when the configured cap is blank', function () {
    collect(range(1, 3))->each(fn () => trimCapRemoteStatus());

    $viewer = User::factory()->create();
    $viewer->refresh();
    Passport::actingAs($viewer, ['read']);

    $res = $this->getJson('/api/v1/timelines/public?remote=1&limit=10')
        ->assertOk()
        ->json();

    expect($res)->not->toBeEmpty();
});

it('bounds the network warm query when the configured cap is blank', function () {
    collect(range(1, 3))->each(fn () => trimCapRemoteStatus());

    $viewer = User::factory()->create();
    $viewer->refresh();
    Passport::actingAs($viewer, ['read']);

    $sql = trimCapCandidateSql(function () {
        $this->getJson('/api/v1/timelines/public?remote=1&limit=10')->assertOk();
    });

    expect(collect($sql)->contains(fn ($q) => str_contains($q, 'limit 100')))->toBeTrue();
});

it('publishes through a forced warm when the configured cap is blank', function () {
    $statuses = collect(range(1, 3))->map(fn () => trimCapRemoteStatus());

    NetworkTimelineService::warmCache(true);

    $cached = collect(NetworkTimelineService::get(0, -1))->map(fn ($v) => (int) $v)->all();

    foreach ($statuses as $status) {
        expect($cached)->toContain((int) $status->id);
    }
});
