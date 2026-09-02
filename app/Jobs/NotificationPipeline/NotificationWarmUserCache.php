<?php

namespace App\Jobs\NotificationPipeline;

use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\UniqueFor;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

#[Tries(3)]
#[Backoff([10, 30, 90])]
#[UniqueFor(3600)]
#[MaxExceptions(2)]
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
