<?php

use App\Jobs\MediaPipeline\EmojiMigrateToCloudPipeline;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * When cloud storage is enabled, custom emoji URLs resolve to the cloud
     * disk. Existing emoji may still only exist locally, so migrate them to
     * cloud to avoid a window where their URLs 404.
     *
     * The upload is potentially large and network-bound, so we dispatch it to
     * the queue and return immediately rather than blocking the upgrade. The
     * work runs in the background on Horizon. No-op on local-only instances.
     */
    public function up(): void
    {
        // Consider cloud enabled if either the live config or the (possibly
        // cached) config_cache value says so; the job guards again at runtime.
        $cloudEnabled = (bool) config('pixelfed.cloud_storage') || (bool) config_cache('pixelfed.cloud_storage');

        if (! $cloudEnabled) {
            return;
        }

        EmojiMigrateToCloudPipeline::dispatch()->onQueue('mmo');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
