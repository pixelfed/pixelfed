<?php

namespace App\Jobs\MediaPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class EmojiMigrateToCloudPipeline implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Bulk emoji upload can take a while; allow up to an hour.
     */
    public $timeout = 3600;

    /**
     * Do not auto-retry a long bulk transfer. It is safe to re-dispatch
     * manually, but automatic retries would re-run the whole upload.
     */
    public $tries = 1;

    /**
     * Only one migration job should be queued/running at a time.
     */
    public $uniqueFor = 3600;

    public function uniqueId(): string
    {
        return 'emoji-migrate-to-cloud';
    }

    public function handle(): void
    {
        // Guard again at run time in case cloud storage was toggled off between
        // dispatch and execution.
        $cloudEnabled = (bool) config('pixelfed.cloud_storage') || (bool) config_cache('pixelfed.cloud_storage');

        if (! $cloudEnabled) {
            Log::info('EmojiMigrateToCloudPipeline: cloud storage not enabled, skipping.');

            return;
        }

        Log::info('EmojiMigrateToCloudPipeline: starting emoji migration to cloud.');

        Artisan::call('admin:EmojiMoveStorageLocalToCloud', [
            '--force' => true,
            '--concurrency' => 100,
        ]);

        Log::info('EmojiMigrateToCloudPipeline: finished. '.trim(Artisan::output()));
    }
}
