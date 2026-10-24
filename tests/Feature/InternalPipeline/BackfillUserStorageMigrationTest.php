<?php

use App\Jobs\InternalPipeline\RecalculateAllUserStoragePipeline;
use Illuminate\Support\Facades\Bus;

/*
| The upgrade backfill migration must dispatch the recalculation job to the
| queue (not run it inline) so a deploy is never blocked recomputing every
| user. Loading the migration file and running up() with a faked bus asserts
| the dispatch without touching the schema.
*/

it('dispatches the recalculation job to the low queue', function () {
    Bus::fake();

    $migration = require base_path('database/migrations/2026_09_10_000001_backfill_user_storage_used.php');
    $migration->up();

    Bus::assertDispatched(RecalculateAllUserStoragePipeline::class, function ($job) {
        // Dispatched onto the low-priority maintenance queue.
        return $job->queue === 'low';
    });
});
