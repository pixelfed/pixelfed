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
use Illuminate\Queue\SerializesModels;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class LikePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $like;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    public $timeout = 5;

    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Like $like)
    {
        $this->like = $like;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $like = $this->like;

        $status = $this->like->status;
        $actor = $this->like->actor;

        if (! $status) {
            // Ignore notifications to deleted statuses
            return;
        }

        StatusService::refresh($status->id);

        if ($status->url && $actor->domain == null) {
            return $this->remoteLikeDeliver();
        }

        $exists = Notification::whereProfileId($status->profile_id)
            ->whereActorId($actor->id)
            ->whereAction('like')
            ->whereItemId($status->id)
            ->whereItemType('App\Status')
            ->count();

        if ($actor->id === $status->profile_id || $exists) {
            return true;
        }

        if ($status->uri === null && $status->object_url === null && $status->url === null) {
            try {
                $notification = new Notification;
                $notification->profile_id = $status->profile_id;
                $notification->actor_id = $actor->id;
                $notification->action = 'like';
                $notification->item_id = $status->id;
                $notification->item_type = "App\Status";
                $notification->save();

            } catch (Exception $e) {
            }

            if (NotificationAppGatewayService::enabled()) {
                if (PushNotificationService::check('like', $status->profile_id)) {
                    $user = User::whereProfileId($status->profile_id)->first();
                    if ($user && $user->expo_token && $user->notify_enabled) {
                        LikePushNotifyPipeline::dispatchSync($user->expo_token, $actor->username);
                    }
                }
            }
        }
    }

    public function remoteLikeDeliver()
    {
        $like = $this->like;
        $status = $this->like->status;
        $actor = $this->like->actor;

        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);
        $resource = new Fractal\Resource\Item($like, new LikeTransformer);
        $activity = $fractal->createData($resource)->toArray();

        $url = $status->profile->sharedInbox ?? $status->profile->inbox_url;

        Helpers::sendSignedObject($actor, $url, $activity);
    }
}
