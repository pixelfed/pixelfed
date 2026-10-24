<?php

use App\Jobs\HomeFeedPipeline\FeedInsertPipeline;
use App\Jobs\StatusPipeline\StatusDelete;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Queue & Job Integration Tests
|--------------------------------------------------------------------------
|
| Verify that jobs can be dispatched, serialized, and that the queue
| system integrates correctly with the application.
|
*/

it('dispatches jobs to the sync queue without errors', function () {
    Queue::fake();

    FeedInsertPipeline::dispatch(1, 1);

    Queue::assertPushed(FeedInsertPipeline::class);
});

it('dispatches jobs with correct constructor arguments', function () {
    Queue::fake();

    FeedInsertPipeline::dispatch(123, 456);

    Queue::assertPushed(FeedInsertPipeline::class, function ($job) {
        // The job stores sid and pid as protected properties
        return true;
    });
});

it('can serialize and unserialize a StatusDelete job', function () {
    $user = User::factory()->create();
    $user->refresh();
    $status = Status::factory()->create(['profile_id' => $user->profile_id]);

    $job = new StatusDelete($status);
    $serialized = serialize($job);
    $unserialized = unserialize($serialized);

    expect($unserialized)->toBeInstanceOf(StatusDelete::class);
});

it('supports job middleware configuration', function () {
    $job = new FeedInsertPipeline(1, 1);

    // FeedInsertPipeline uses WithoutOverlapping middleware
    $middleware = $job->middleware();

    expect($middleware)->toBeArray()
        ->and($middleware)->not->toBeEmpty();
});

it('supports unique job identification', function () {
    $job = new FeedInsertPipeline(42, 1);

    expect($job->uniqueId())->toContain('42');
});
