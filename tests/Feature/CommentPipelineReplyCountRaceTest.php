<?php

use App\Jobs\CommentPipeline\CommentPipeline;
use App\Jobs\StatusPipeline\StatusDelete;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| CommentPipeline reply_count must not lose concurrent increments
|--------------------------------------------------------------------------
|
| The old read-modify-write ($status->reply_count + 1; $status->save();)
| lost an increment when two jobs rehydrated the same parent. The atomic
| Status::whereId(...)->increment('reply_count') is race-safe: two pipelines
| constructed from the same stale parent snapshot both count.
|
*/

it('counts both replies even when both jobs hold a stale parent snapshot', function () {
    $author = User::factory()->create();
    $author->refresh();
    $replier = User::factory()->create();
    $replier->refresh();

    $parent = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo',
        'reply_count' => 0,
    ]);

    // Two stale snapshots of the parent, each still reading reply_count = 0,
    // mirroring two workers that rehydrated before either wrote back.
    $staleA = Status::find($parent->id);
    $staleB = Status::find($parent->id);

    $replyA = Status::factory()->create([
        'profile_id' => $replier->profile_id,
        'in_reply_to_id' => $parent->id,
        'in_reply_to_profile_id' => $author->profile_id,
    ]);
    $replyB = Status::factory()->create([
        'profile_id' => $replier->profile_id,
        'in_reply_to_id' => $parent->id,
        'in_reply_to_profile_id' => $author->profile_id,
    ]);

    (new CommentPipeline($staleA, $replyA))->handle();
    (new CommentPipeline($staleB, $replyB))->handle();

    expect((int) Status::find($parent->id)->reply_count)->toBe(2);
});

it('decrements reply_count for both deleted replies without losing an update', function () {
    config(['federation.activitypub.enabled' => false]);

    $author = User::factory()->create();
    $author->refresh();
    $replier = User::factory()->create();
    $replier->refresh();

    // Parent already has two replies counted.
    $parent = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo',
        'reply_count' => 2,
    ]);

    $replyA = Status::factory()->create([
        'profile_id' => $replier->profile_id,
        'in_reply_to_id' => $parent->id,
        'in_reply_to_profile_id' => $author->profile_id,
    ]);
    $replyB = Status::factory()->create([
        'profile_id' => $replier->profile_id,
        'in_reply_to_id' => $parent->id,
        'in_reply_to_profile_id' => $author->profile_id,
    ]);

    (new StatusDelete($replyA))->handle();
    (new StatusDelete($replyB))->handle();

    expect((int) Status::find($parent->id)->reply_count)->toBe(0);
});

it('never decrements reply_count below zero', function () {
    config(['federation.activitypub.enabled' => false]);

    $author = User::factory()->create();
    $author->refresh();
    $replier = User::factory()->create();
    $replier->refresh();

    // Parent count is already 0 (drifted low); a delete must not underflow.
    $parent = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo',
        'reply_count' => 0,
    ]);

    $reply = Status::factory()->create([
        'profile_id' => $replier->profile_id,
        'in_reply_to_id' => $parent->id,
        'in_reply_to_profile_id' => $author->profile_id,
    ]);

    (new StatusDelete($reply))->handle();

    expect((int) Status::find($parent->id)->reply_count)->toBe(0);
});
