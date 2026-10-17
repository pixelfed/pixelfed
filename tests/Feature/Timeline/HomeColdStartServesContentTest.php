<?php

use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

afterEach(function () {
    Redis::flushdb();
});

it('serves warmed content with 200 on a cold-start read', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $author = User::factory()->create();
    $author->refresh();
    Follower::create(['profile_id' => $pid, 'following_id' => $author->profile_id]);

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'in_reply_to_id' => null, 'reblog_of_id' => null, 'uri' => null,
        'created_at' => now()->subMinutes(5),
    ]);
    StatusService::getMastodon($status->id, false);

    // Feed is cold (no cached set); the synchronous warm should populate it
    // from the follow and the first read should serve the post with 200.
    expect(HomeTimelineService::cacheKeyExists($pid))->toBeFalse();

    Passport::actingAs($viewer, ['read']);

    $res = $this->getJson('/api/v1/timelines/home')->assertOk()->json();

    $ids = collect($res)->pluck('id')->map(fn ($id) => (int) $id)->all();

    expect($ids)->toContain((int) $status->id);
});

it('returns 200 empty when a cold feed warms to nothing', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    // Follows an account with no eligible posts.
    $author = User::factory()->create();
    $author->refresh();
    Follower::create(['profile_id' => $pid, 'following_id' => $author->profile_id]);

    Passport::actingAs($viewer, ['read']);

    $res = $this->getJson('/api/v1/timelines/home')->assertOk();

    expect($res->json())->toBe([]);
});
