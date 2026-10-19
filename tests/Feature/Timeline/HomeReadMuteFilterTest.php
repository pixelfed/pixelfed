<?php

use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Services\HomeTimelineService;
use App\Services\StatusService;
use App\Services\UserFilterService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

afterEach(function () {
    Redis::flushdb();
});

it('hides a muted account from the home feed at read time', function () {
    $viewer = User::factory()->create();
    $viewer->refresh();
    $pid = $viewer->profile_id;

    $keep = User::factory()->create();
    $keep->refresh();
    $muted = User::factory()->create();
    $muted->refresh();

    foreach ([$keep, $muted] as $u) {
        Follower::create(['profile_id' => $pid, 'following_id' => $u->profile_id]);
    }

    $keptStatus = Status::factory()->create([
        'profile_id' => $keep->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'in_reply_to_id' => null, 'reblog_of_id' => null,
    ]);
    $mutedStatus = Status::factory()->create([
        'profile_id' => $muted->profile_id,
        'type' => 'photo', 'scope' => 'public', 'visibility' => 'public',
        'in_reply_to_id' => null, 'reblog_of_id' => null,
    ]);
    StatusService::getMastodon($keptStatus->id, false);
    StatusService::getMastodon($mutedStatus->id, false);

    HomeTimelineService::add($pid, $keptStatus->id);
    HomeTimelineService::add($pid, $mutedStatus->id);

    // Mute after the post already landed in the cached feed.
    UserFilterService::mute($pid, $muted->profile_id);

    Passport::actingAs($viewer, ['read']);

    $ids = collect($this->getJson('/api/v1/timelines/home?limit=40')->assertOk()->json())
        ->pluck('id')->map(fn ($id) => (int) $id)->all();

    expect($ids)->toContain((int) $keptStatus->id)
        ->and($ids)->not->toContain((int) $mutedStatus->id);
});
