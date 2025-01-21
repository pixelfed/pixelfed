<?php

namespace App\Jobs\PushNotificationPipeline;

use App\Services\NotificationAppGatewayService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MentionPushNotifyPipeline implements ShouldQueue
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
        try {
            NotificationAppGatewayService::send($this->pushToken, 'mention', $this->actor);
        } catch (Exception $e) {
            return;
        }
    }
}
