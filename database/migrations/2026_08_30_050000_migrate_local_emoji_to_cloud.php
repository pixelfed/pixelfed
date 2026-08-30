<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * When cloud storage is enabled, custom emoji URLs resolve to the cloud
     * disk. Existing emoji may still only exist locally, so move them all to
     * cloud now (in one pass) to avoid a window where their URLs 404. This is
     * a no-op on local-only instances.
     */
    public function up(): void
    {
        // Consider cloud enabled if either the live config or the (possibly
        // cached) config_cache value says so; the command guards again anyway.
        $cloudEnabled = (bool) config('pixelfed.cloud_storage') || (bool) config_cache('pixelfed.cloud_storage');

        if (! $cloudEnabled) {
            return;
        }

        Artisan::call('admin:EmojiMoveStorageLocalToCloud', [
            '--force' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
