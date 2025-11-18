<?php

namespace App\Jobs\LikePipeline;

use App\Like;
use App\Notification;
use App\Services\StatusService;
use App\Transformer\ActivityPub\Verb\UndoLike as LikeTransformer;
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

class UnlikePipeline implements ShouldQueue
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
            (new WithoutOverlapping("unlike:{$this->like->status_id}:{$this->like->profile_id}"))
                ->releaseAfter(10)
                ->expireAfter(60),
        ];
    }

    public function uniqueId()
    {
        return "unlike:{$this->like->status_id}:{$this->like->profile_id}";
    }

    public function handle()
    {
        $like = $this->like;
        $status = $like->status;
        $actor = $like->actor;

        if (! $status) {
            return;
        }

        DB::transaction(function () use ($status, $actor, $like) {
            if ($status->likes_count > 0) {
                $status->decrement('likes_count');
            }

            Notification::whereProfileId($status->profile_id)
                ->whereActorId($actor->id)
                ->whereAction('like')
                ->whereItemId($status->id)
                ->whereItemType('App\Status')
                ->chunkById(100, function ($notifications) {
                    foreach ($notifications as $notification) {
                        $notification->forceDelete();
                    }
                });

            $like->forceDelete();
        });

        if ($actor->id !== $status->profile_id && $status->url && $actor->domain == null) {
            $this->remoteLikeDeliver();
        }

        StatusService::refresh($status->id);
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
