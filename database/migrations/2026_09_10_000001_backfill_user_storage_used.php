<?php

use App\Jobs\InternalPipeline\RecalculateAllUserStoragePipeline;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Repair storage_used counters that drifted before the self-heal logic
     * existed (#7169). The recalculation is dispatched to the queue rather than
     * run inline so the deploy is not blocked while every user is recomputed.
     *
     * The job is idempotent (recomputes each user from source) and unique, so a
     * re-run of this migration or a duplicate dispatch is harmless.
     */
    public function up(): void
    {
        RecalculateAllUserStoragePipeline::dispatch()->onQueue('low');
    }

    public function down(): void
    {
        // Data backfill only; nothing to reverse.
    }
};
