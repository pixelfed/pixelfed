<?php

namespace App\Jobs\InternalPipeline;

use App\Notification;
use App\Services\NotificationService;
use Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class NotificationEpochUpdatePipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1500;

    public $tries = 3;

    public $maxExceptions = 1;

    public $failOnTimeout = true;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 3600;

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
