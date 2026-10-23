<?php

use App\Jobs\MentionPipeline\MentionPipeline;
use App\Models\Mention;
use App\Models\Notification;
use App\Models\Status;
use App\Models\User;
use App\Services\UserFilterService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| MentionPipeline must not notify a target who blocked the actor
|--------------------------------------------------------------------------
|
| MentionPipeline is the shared sink for every mention path (including AP
| ingest, which dispatches with no block check). Without a block check here,
| a blocked account could still mention -> notify -> push-notify its target,
| bypassing the block. The notification must be suppressed when the target
| has blocked the actor.
|
*/

beforeEach(function () {
    Queue::fake();
});

function mentionSetup(): array
{
    $actor = User::factory()->create();
    $actor->refresh();
    $target = User::factory()->create();
    $target->refresh();

    $status = Status::factory()->create([
        'profile_id' => $actor->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
    ]);

    $mention = new Mention;
    $mention->status_id = $status->id;
    $mention->profile_id = $target->profile_id;
    $mention->save();

    return [$actor, $target, $status, $mention];
}

it('does not create a mention notification when the target blocked the actor', function () {
    [$actor, $target, $status, $mention] = mentionSetup();

    // Target blocks the actor (warms the block cache).
    UserFilterService::block($target->profile_id, $actor->profile_id);

    (new MentionPipeline($status, $mention))->handle();

    expect(Notification::whereProfileId($target->profile_id)
        ->whereActorId($actor->profile_id)
        ->whereAction('mention')
        ->count())->toBe(0);
});

it('creates a mention notification when there is no block', function () {
    [$actor, $target, $status, $mention] = mentionSetup();

    (new MentionPipeline($status, $mention))->handle();

    expect(Notification::whereProfileId($target->profile_id)
        ->whereActorId($actor->profile_id)
        ->whereAction('mention')
        ->count())->toBe(1);
});
