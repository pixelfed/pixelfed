<?php

namespace App\Jobs\PushNotificationPipeline;

use App\Services\NotificationAppGatewayService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class LikePushNotifyPipeline implements ShouldQueue
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
            Log::info("LikePushNotifyPipeline: Push token not provided, skipping job");
            return;
        }

        // Verify actor exists
        if (!$actor) {
            Log::info("LikePushNotifyPipeline: Actor not provided, skipping job");
            return;
        }

        try {
            NotificationAppGatewayService::send($pushToken, 'like', $actor);
        } catch (Exception $e) {
            Log::warning("NotificationAppGatewayService: Failed to send Like notification to {$actor} :" . $e->getMessage());
            return;
        }
    }
}
