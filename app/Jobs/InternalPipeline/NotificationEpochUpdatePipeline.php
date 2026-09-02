<?php

namespace App\Jobs\InternalPipeline;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\FailOnTimeout;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\UniqueFor;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

#[Timeout(1500)]
#[Tries(3)]
#[MaxExceptions(1)]
#[FailOnTimeout]
#[UniqueFor(3600)]
class NotificationEpochUpdatePipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'ip:notification-epoch-update';
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('ip:notification-epoch-update'))->shared()->dontRelease()];
    }

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $pid = Cache::get(NotificationService::EPOCH_CACHE_KEY.'6');
        if ($pid && $pid > 1) {
            $rec = Notification::where('id', '>', $pid)->whereDate('created_at', now()->subMonths(6)->format('Y-m-d'))->first();
        } else {
            $rec = Notification::whereDate('created_at', now()->subMonths(6)->format('Y-m-d'))->first();
        }
        $id = 1;
        if ($rec) {
            $id = $rec->id;
        }
        Cache::put(NotificationService::EPOCH_CACHE_KEY.'6', $id, 1209600);
    }
}
