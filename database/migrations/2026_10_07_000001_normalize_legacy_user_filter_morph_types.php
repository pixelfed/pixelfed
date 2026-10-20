<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rewrite legacy user_filters rows that still store the old morphMap
     * alias (App\Profile) to the canonical model class (App\Models\Profile).
     *
     * Rows written before the namespace migration kept the short alias, so
     * the block/mute reads that match only the modern class skipped them.
     * Collisions on the filter_unique tuple (user_id, filterable_id,
     * filterable_type, filter_type) are removed first so the rewrite below
     * never trips the unique index, then the survivors are normalized.
     */
    public function up(): void
    {
        if (! Schema::hasTable('user_filters')) {
            return;
        }

        DB::table('user_filters as legacy')
            ->where('legacy.filterable_type', 'App\Profile')
            ->whereExists(function ($q) {
                $q->from('user_filters as modern')
                    ->whereColumn('modern.user_id', 'legacy.user_id')
                    ->whereColumn('modern.filterable_id', 'legacy.filterable_id')
                    ->whereColumn('modern.filter_type', 'legacy.filter_type')
                    ->where('modern.filterable_type', 'App\Models\Profile');
            })
            ->delete();

        DB::table('user_filters')
            ->where('filterable_type', 'App\Profile')
            ->update(['filterable_type' => 'App\Models\Profile']);
    }

    /**
     * Not reversible: the legacy alias is ambiguous and reverting would
     * reintroduce the morph-type mismatch this migration exists to fix.
     */
    public function down(): void
    {
        //
    }
};
