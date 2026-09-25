<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nodeinfo crawling reused the ActivityPub delivery-backoff columns
        // (delivery_timeout / delivery_next_after), so a failed Nodeinfo fetch
        // could mark a sub-threshold host unavailable for delivery. Give the
        // Nodeinfo crawler its own cooldown columns.
        Schema::table('instances', function (Blueprint $table) {
            if (! Schema::hasColumn('instances', 'nodeinfo_timeout')) {
                $table->boolean('nodeinfo_timeout')->default(false);
            }

            if (! Schema::hasColumn('instances', 'nodeinfo_next_after')) {
                $table->timestamp('nodeinfo_next_after')->nullable();
            }
        });

        // Backfill: any host below the delivery failure threshold should not be
        // carrying a delivery backoff. Nodeinfo failures previously wrote these
        // columns without touching delivery_failures, so clear that stale state
        // (applyFailure() re-arms it on the next threshold-crossing failure).
        $threshold = max(1, (int) config('federation.activitypub.delivery.failure_threshold', 5));

        DB::table('instances')
            ->where('delivery_failures', '<', $threshold)
            ->update([
                'delivery_timeout' => false,
                'delivery_next_after' => null,
            ]);
    }

    public function down(): void
    {
        Schema::table('instances', function (Blueprint $table) {
            if (Schema::hasColumn('instances', 'nodeinfo_next_after')) {
                $table->dropColumn('nodeinfo_next_after');
            }

            if (Schema::hasColumn('instances', 'nodeinfo_timeout')) {
                $table->dropColumn('nodeinfo_timeout');
            }
        });
    }
};
