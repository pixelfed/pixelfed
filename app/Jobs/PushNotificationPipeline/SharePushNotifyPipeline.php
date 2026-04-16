<?php

namespace App\Jobs\PushNotificationPipeline;

use App\Services\NotificationAppGatewayService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SharePushNotifyPipeline implements ShouldQueue
{
  use Queueable;

  public $pushToken;

  public $actor;

  /**
  * Create a new job instance
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

    // Verify Push Token exists
    if (! $pushToken) {
      Log::info('SharePushNotifyPipeline: Push token not provided, skipping job');

      return;
    }

    // Verify actor exists
    if (! $actor) {
      Log::info('SharePushNotifyPipeline: Actor not provided, skipping job');

      return;
    }

    try {
      NotificationAppGatewayService::send($pushToken, 'share', $actor);
    } catch (Exception $e) {
      Log::warning("NotificationAppGatewayService: Failed to send reblog notification to {$actor} :".$e->getMessage());

      return;
    }
  }
}
