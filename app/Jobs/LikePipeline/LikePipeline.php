<?php

namespace App\Jobs\LikePipeline;

use App\Jobs\PushNotificationPipeline\LikePushNotifyPipeline;
use App\Like;
use App\Notification;
use App\Services\NotificationAppGatewayService;
use App\Services\PushNotificationService;
use App\Services\StatusService;
use App\Transformer\ActivityPub\Verb\Like as LikeTransformer;
use App\User;
use App\Util\ActivityPub\Helpers;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class LikePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $like;

    public $deleteWhenMissingModels = true;

    public $timeout = 30;

    public $tries = 3;

    public $maxExceptions = 2;

    public $backoff = [3, 10];

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
                $notification = Notification::firstOrCreate(
                    [
                        'profile_id' => $status->profile_id,
                        'actor_id' => $actor->id,
                        'action' => 'like',
                        'item_id' => $status->id,
                        'item_type' => 'App\Status',
                    ]
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

        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);
        $resource = new Fractal\Resource\Item($like, new LikeTransformer);
        $activity = $fractal->createData($resource)->toArray();

        $url = $status->profile->sharedInbox ?? $status->profile->inbox_url;

        Helpers::sendSignedObject($actor, $url, $activity);
    }
}
