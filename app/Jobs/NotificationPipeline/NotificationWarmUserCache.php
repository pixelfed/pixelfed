<?php

namespace App\Jobs\NotificationPipeline;

use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotificationWarmUserCache implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The profile ID to warm cache for.
     *
     * @var int
     */
    public $pid;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     * This creates exponential backoff: 10s, 30s, 90s
     *
     * @var array
     */
    public $backoff = [10, 30, 90];

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 3600; // 1 hour

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 2;

    /**
     * Create a new job instance.
     *
     * @param  int  $pid  The profile ID to warm cache for
     * @return void
     */
    public function __construct(int $pid)
    {
        $this->pid = $pid;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'notifications:profile_warm_cache:'.$this->pid;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            NotificationService::warmCache($this->pid, 100, true);
        } catch (\Exception $e) {
            Log::error('Failed to warm notification cache', [
                'profile_id' => $this->pid,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            throw $e;
        }
    }
}
