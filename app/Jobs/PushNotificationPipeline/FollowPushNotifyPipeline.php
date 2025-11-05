<?php

namespace App\Jobs\PushNotificationPipeline;

use App\Services\NotificationAppGatewayService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FollowPushNotifyPipeline implements ShouldQueue
{
    use Queueable;

    public $pushToken;

    public $actor;

    /**
     * Create a new job instance.
     */
    public function __construct($pushToken, $actor)
    {
        $this->pushToken = $pushToken;
        $this->actor = $actor;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $pushToken = $this->pushToken;
        $actor = $this->actor;

        // Verify push token exists
        if (!$pushToken) {
            Log::info("FollowPushNotifyPipeline: Push token not provided, skipping job");
            return;
        }

        // Verify actor exists
        if (!$actor) {
            Log::info("FollowPushNotifyPipeline: Actor not provided, skipping job");
            return;
        }

        try {
            NotificationAppGatewayService::send($pushToken, 'follow', $actor);
        } catch (Exception $e) {
            Log::warning("FollowPushNotifyPipeline: Failed to send Follow notification to {$actor} :" . $e->getMessage());
            return;
        }
    }
}
