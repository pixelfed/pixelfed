<?php

namespace App\Jobs\LikePipeline;

use App\Jobs\PushNotificationPipeline\LikePushNotifyPipeline;
use App\Models\Like;
use App\Models\Status;
use App\Models\User;
use App\Services\FractalService;
use App\Services\NotificationAppGatewayService;
use App\Services\NotificationService;
use App\Services\PushNotificationService;
use App\Services\StatusService;
use App\Transformer\ActivityPub\Verb\Like as LikeTransformer;
use App\Util\ActivityPub\Helpers;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

#[DeleteWhenMissingModels]
#[Timeout(30)]
#[Tries(3)]
#[MaxExceptions(2)]
#[Backoff([3, 10])]
class LikePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $like;

    public function __construct(Like $like)
    {
        $this->like = $like;
    }

    public function middleware()
    {
        return [
            (new WithoutOverlapping("like:{$this->like->status_id}:{$this->like->profile_id}"))
                ->releaseAfter(10)
                ->expireAfter(60),
        ];
    }

    public function uniqueId()
    {
        return "like:{$this->like->status_id}:{$this->like->profile_id}";
    }

    public function handle()
    {
        $like = $this->like;
        $status = $like->status;
        $actor = $like->actor;

        if (! $status) {
            return;
        }

        if ($status->url && $actor->domain == null) {
            $this->remoteLikeDeliver();
            StatusService::refresh($status->id);

            return;
        }

        if ($actor->id === $status->profile_id) {
            StatusService::refresh($status->id);

            return;
        }

        if ($status->uri === null && $status->object_url === null && $status->url === null) {
            DB::transaction(function () use ($status, $actor) {
                $notification = NotificationService::firstOrCreateNotification(
                    $status->profile_id, $actor->id, 'like', $status->id, Status::class
                );

                if ($notification->wasRecentlyCreated) {
                    $this->sendPushNotification($status, $actor);
                }
            });
        }

        StatusService::refresh($status->id);
    }

    protected function sendPushNotification($status, $actor)
    {
        if (! NotificationAppGatewayService::enabled()) {
            return;
        }

        if (! PushNotificationService::check('like', $status->profile_id)) {
            return;
        }

        $user = User::whereProfileId($status->profile_id)->first();

        if ($user && $user->expo_token && $user->notify_enabled) {
            LikePushNotifyPipeline::dispatchSync($user->expo_token, $actor->username);
        }
    }

    public function remoteLikeDeliver()
    {
        $like = $this->like;
        $status = $like->status;
        $actor = $like->actor;

        $activity = FractalService::item($like, new LikeTransformer);

        $url = $status->profile->sharedInbox ?? $status->profile->inbox_url;

        Helpers::sendSignedObject($actor, $url, $activity);
    }
}
