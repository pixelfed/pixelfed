<?php

namespace App\Jobs\MentionPipeline;

use App\Jobs\PushNotificationPipeline\MentionPushNotifyPipeline;
use App\Models\Mention;
use App\Models\Notification;
use App\Models\Status;
use App\Models\User;
use App\Services\NotificationAppGatewayService;
use App\Services\NotificationService;
use App\Services\PushNotificationService;
use App\Services\StatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

#[DeleteWhenMissingModels]
class MentionPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;

    protected $mention;

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
        if (! $status) {
            Log::info('MentionPipeline: Status no longer exists, skipping job');

            return;
        }

        // Verify mention exists
        if (! $mention) {
            Log::info('MentionPipeline: Mention no longer exists, skipping job');

            return;
        }

        $actor = $status->profile;
        $target = $mention->profile_id;

        // Verify actor profile exists
        if (! $actor) {
            Log::info("MentionPipeline: Actor profile no longer exists for status {$status->id}, skipping job");

            return;
        }

        // Verify target profile ID exists
        if (! $target) {
            Log::info("MentionPipeline: Target profile ID missing for mention {$mention->id}, skipping job");

            return;
        }

        $exists = Notification::whereProfileId($target)
            ->whereActorId($actor->id)
            ->whereIn('action', ['mention', 'comment'])
            ->whereItemId($status->id)
            ->whereItemType(Status::class)
            ->count();

        if ($actor->id === $target || $exists !== 0) {
            return;
        }

        NotificationService::firstOrCreateNotification($target, $actor->id, 'mention', $status->id, Status::class);

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
