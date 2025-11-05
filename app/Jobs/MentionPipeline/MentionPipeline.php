<?php

namespace App\Jobs\MentionPipeline;

use App\Mention;
use App\Notification;
use App\Status;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\PushNotificationPipeline\MentionPushNotifyPipeline;
use App\Services\NotificationAppGatewayService;
use App\Services\PushNotificationService;
use App\Services\StatusService;
use Illuminate\Support\Facades\Log;

class MentionPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;
    protected $mention;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Status $status, Mention $mention)
    {
        $this->status = $status;
        $this->mention = $mention;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $status = $this->status;
        $mention = $this->mention;

        // Verify status exists
        if (!$status) {
            Log::info("MentionPipeline: Status no longer exists, skipping job");
            return;
        }

        // Verify mention exists
        if (!$mention) {
            Log::info("MentionPipeline: Mention no longer exists, skipping job");
            return;
        }

        $actor = $status->profile;
        $target = $mention->profile_id;

        // Verify actor profile exists
        if (!$actor) {
            Log::info("MentionPipeline: Actor profile no longer exists for status {$status->id}, skipping job");
            return;
        }

        // Verify target profile ID exists
        if (!$target) {
            Log::info("MentionPipeline: Target profile ID missing for mention {$mention->id}, skipping job");
            return;
        }

        $exists = Notification::whereProfileId($target)
                  ->whereActorId($actor->id)
                  ->whereIn('action', ['mention', 'comment'])
                  ->whereItemId($status->id)
                  ->whereItemType('App\Status')
                  ->count();

        if ($actor->id === $target || $exists !== 0) {
            return;
        }

        Notification::firstOrCreate(
            [
                'profile_id' => $target,
                'actor_id' => $actor->id,
                'action' => 'mention',
                'item_type' => 'App\Status',
                'item_id' => $status->id,
            ]
        );

        StatusService::del($status->id);

        if (NotificationAppGatewayService::enabled()) {
            if (PushNotificationService::check('mention', $target)) {
                $user = User::whereProfileId($target)->first();
                if ($user && $user->expo_token && $user->notify_enabled) {
                    MentionPushNotifyPipeline::dispatch($user->expo_token, $actor->username)->onQueue('pushnotify');
                }
            }
        }
    }
}
