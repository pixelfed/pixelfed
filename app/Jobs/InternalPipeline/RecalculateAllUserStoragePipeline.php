<?php

namespace App\Jobs\InternalPipeline;

use App\Models\User;
use App\Services\UserStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

/**
 * One-off backfill that recomputes users.storage_used from actual media.
 *
 * Repairs accounts whose cached storage counter drifted upward before the
 * upload/delete self-heal logic existed, unblocking users stuck at the
 * account size limit despite low real usage (#7169). Safe to run repeatedly:
 * each user is recomputed from source, so it is idempotent.
 */
class RecalculateAllUserStoragePipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;

    public $tries = 1;

    public $maxExceptions = 1;

    public $failOnTimeout = true;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 7200;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'ip:recalculate-all-user-storage';
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('ip:recalculate-all-user-storage'))->shared()->dontRelease()];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        User::whereNull('status')
            ->chunkById(500, function ($users) {
                foreach ($users as $user) {
                    UserStorageService::recalculateUpdateStorageUsed($user->id);
                }
            });
    }
}
