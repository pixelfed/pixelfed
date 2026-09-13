<?php

use App\Enums\MediaQuotaStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Storage-accounting columns for the media quota lifecycle.
     *
     * `original_size` records the raw uploaded byte count. Upload-time quota
     * enforcement is based on the raw size (a user cannot upload an original
     * larger than their remaining quota).
     *
     * `quota_status` tracks how much of the media is currently reflected in the
     * owner's users.storage_used counter (see App\Enums\MediaQuotaStatus). The
     * quota is charged the raw size at upload, corrected down to the optimized
     * size by the async finalize job, and refunded on delete. Each transition
     * is guarded by this status so retries cannot double-apply a delta.
     */
    public function up(): void
    {
        // New uploads begin their quota lifecycle at pending.
        Schema::table('media', function (Blueprint $table) {
            $table->unsignedInteger('original_size')->nullable()->after('size');
            $table->string('quota_status', 20)
                ->default(MediaQuotaStatus::Pending->value)
                ->index()
                ->after('original_size');
        });

        // Existing rows were already counted at their (optimized) `size` under
        // the previous scheme, so mark them optimized_size to keep the delete
        // refund correct. Skipped for empty tables (fresh installs).
        DB::table('media')->update(['quota_status' => MediaQuotaStatus::OptimizedSize->value]);
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn(['original_size', 'quota_status']);
        });
    }
};
