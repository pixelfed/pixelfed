<?php

namespace App\Jobs\LikePipeline;

use Cache, Log;
use Illuminate\Support\Facades\Redis;
use App\{Like, Notification};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Util\ActivityPub\Helpers;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use App\Transformer\ActivityPub\Verb\Like as LikeTransformer;

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

        if (!$status) {
            // Ignore notifications to deleted statuses
            return;
        }

        if($status->url && $actor->domain == null) {
            return $this->remoteLikeDeliver();
        }

        $exists = Notification::whereProfileId($status->profile_id)
                  ->whereActorId($actor->id)
                  ->whereAction('like')
                  ->whereItemId($status->id)
                  ->whereItemType('App\Status')
                  ->count();

        if ($actor->id === $status->profile_id || $exists !== 0) {
            return true;
        }

        try {
            $notification = new Notification();
            $notification->profile_id = $status->profile_id;
            $notification->actor_id = $actor->id;
            $notification->action = 'like';
            $notification->message = $like->toText($status->in_reply_to_id ? 'comment' : 'post');
            $notification->rendered = $like->toHtml($status->in_reply_to_id ? 'comment' : 'post');
            $notification->item_id = $status->id;
            $notification->item_type = "App\Status";
            $notification->save();

        } catch (Exception $e) {
        }
    }

    public function remoteLikeDeliver()
    {
        $like = $this->like;
        $status = $this->like->status;
        $actor = $this->like->actor;

        $fractal = new Fractal\Manager();
        $fractal->setSerializer(new ArraySerializer());
        $resource = new Fractal\Resource\Item($like, new LikeTransformer());
        $activity = $fractal->createData($resource)->toArray();

        $url = $status->profile->sharedInbox ?? $status->profile->inbox_url;

        Helpers::sendSignedObject($actor, $url, $activity);
    }
}
