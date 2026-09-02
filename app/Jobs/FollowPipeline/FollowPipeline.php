<?php

namespace App\Jobs\FollowPipeline;

use App\Jobs\PushNotificationPipeline\FollowPushNotifyPipeline;
use App\Models\Follower;
use App\Models\Profile;
use App\Models\User;
use App\Services\AccountService;
use App\Services\FollowerService;
use App\Services\NotificationAppGatewayService;
use App\Services\NotificationService;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

#[DeleteWhenMissingModels]
class FollowPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $follower;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($follower)
    {
        $this->follower = $follower;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $follower = $this->follower;
        $actor = $follower->actor;
        $target = $follower->target;

        if (! $actor || ! $target) {
            return;
        }

        if ($target->domain || ! $target->private_key) {
            return;
        }

        Cache::forget('profile:following:'.$actor->id);
        Cache::forget('profile:following:'.$target->id);

        FollowerService::add($actor->id, $target->id);

        $count = Follower::whereProfileId($actor->id)->count();
        $actor->following_count = $count;
        $actor->save();
        AccountService::del($actor->id);

        $count = Follower::whereFollowingId($target->id)->count();
        $target->followers_count = $count;
        $target->save();
        AccountService::del($target->id);

        if ($target->user_id && $target->domain === null) {
            try {
                NotificationService::createNotification($target->id, $actor->id, 'follow', $target->id, Profile::class);
            } catch (\Exception $e) {
                Log::error($e);
            }

            if (NotificationAppGatewayService::enabled()) {
                if (PushNotificationService::check('follow', $target->id)) {
                    $user = User::whereProfileId($target->id)->first();
                    if ($user && $user->expo_token && $user->notify_enabled) {
                        FollowPushNotifyPipeline::dispatch($user->expo_token, $actor->username)->onQueue('pushnotify');
                    }
                }
            }
        }
    }
}
