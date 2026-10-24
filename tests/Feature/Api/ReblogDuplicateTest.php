<?php

use App\Jobs\SharePipeline\SharePipeline;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| POST /api/v1/statuses/{id}/reblog idempotency
|--------------------------------------------------------------------------
|
| Reblogging the same status twice must not inflate reblogs_count nor create a
| second share row, and must not re-dispatch SharePipeline for an existing
| share.
|
*/

it('does not inflate reblogs_count on duplicate reblog calls', function () {
    $author = User::factory()->create();
    $author->refresh();
    $sharer = User::factory()->create();
    $sharer->refresh();

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'reblogs_count' => 0,
    ]);

    Passport::actingAs($sharer, ['write', 'read']);

    $this->postJson("/api/v1/statuses/{$status->id}/reblog")->assertOk();
    $this->postJson("/api/v1/statuses/{$status->id}/reblog")->assertOk();

    // Exactly one share row for this user/status.
    expect(Status::where('reblog_of_id', $status->id)
        ->where('profile_id', $sharer->profile_id)
        ->where('type', 'share')
        ->count())->toBe(1);

    // Counter reflects a single reblog.
    expect($status->fresh()->reblogs_count)->toBe(1);
});

it('does not re-dispatch SharePipeline for an already-existing share', function () {
    Bus::fake();

    $author = User::factory()->create();
    $author->refresh();
    $sharer = User::factory()->create();
    $sharer->refresh();

    $status = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'reblogs_count' => 0,
    ]);

    Passport::actingAs($sharer, ['write', 'read']);

    $this->postJson("/api/v1/statuses/{$status->id}/reblog")->assertOk();
    $this->postJson("/api/v1/statuses/{$status->id}/reblog")->assertOk();

    // SharePipeline dispatched exactly once (only for the newly-created share).
    Bus::assertDispatchedTimes(SharePipeline::class, 1);
});
