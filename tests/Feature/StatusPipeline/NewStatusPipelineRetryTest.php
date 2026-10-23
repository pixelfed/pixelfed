<?php

use App\Jobs\StatusPipeline\NewStatusPipeline;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| NewStatusPipeline transient-failure retry
|--------------------------------------------------------------------------
|
| tries=3/backoff exists to recover from transient DB/redis blips, but
| maxExceptions=1 made the first uncaught exception fail the job with no
| automatic retry. Transient infrastructure exceptions must now release the
| job (retry via tries=3) while genuine bugs still fail fast.
|
*/

beforeEach(function () {
    Redis::spy();
});

function newStatusPipelineFor(callable $publishThrows): NewStatusPipeline
{
    $user = User::factory()->create();
    $user->refresh();
    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'text',
        'scope' => 'public',
    ]);

    $job = Mockery::mock(NewStatusPipeline::class, [$status])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $job->shouldReceive('publish')->andThrow($publishThrows());

    return $job->withFakeQueueInteractions();
}

it('releases the job for a transient database exception instead of failing', function () {
    $job = newStatusPipelineFor(fn () => new QueryException(
        'mysql',
        'select 1',
        [],
        new RuntimeException('server has gone away')
    ));

    // Must not rethrow: a transient failure releases for retry.
    $job->handle();

    $job->assertReleased();
});

it('rethrows a non-transient exception so it fails fast', function () {
    $job = newStatusPipelineFor(fn () => new RuntimeException('genuine bug'));

    expect(fn () => $job->handle())->toThrow(RuntimeException::class);

    $job->assertNotReleased();
});

it('treats a redis connection exception as transient', function () {
    // phpredis raises RedisException; matched by class name in isTransient().
    $job = newStatusPipelineFor(fn () => new RedisException('connection refused'));

    $job->handle();

    $job->assertReleased();
});
