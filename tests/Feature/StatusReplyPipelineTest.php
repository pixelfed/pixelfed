<?php

use App\Jobs\StatusPipeline\StatusReplyPipeline;
use App\Models\Notification;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Redis::spy();
});

it('increments reply_count even when a mention notification already exists', function () {
    $localUser = User::factory()->create();
    $localUser->refresh();

    $remote = Profile::factory()->remote()->create([
        'domain' => 'remote.example',
        'username' => '@actor@remote.example',
    ]);

    $parent = Status::factory()->create([
        'profile_id' => $localUser->profile_id,
        'reply_count' => 0,
    ]);

    $reply = Status::factory()->create([
        'profile_id' => $remote->id,
        'in_reply_to_id' => $parent->id,
        'in_reply_to_profile_id' => $localUser->profile_id,
        'local' => false,
    ]);

    // Simulate MentionPipeline winning the race
    Notification::factory()->mention()->create([
        'profile_id' => $localUser->profile_id,
        'actor_id' => $remote->id,
        'item_id' => $reply->id,
    ]);

    (new StatusReplyPipeline($reply))->handle();

    $parent->refresh();
    expect($parent->reply_count)->toBe(1);
});
