<?php

namespace App\Jobs\LikePipeline;

use App\Models\Like;
use App\Models\Notification;
use App\Models\Status;
use App\Services\FractalService;
use App\Services\StatusService;
use App\Transformer\ActivityPub\Verb\UndoLike as LikeTransformer;
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
class UnlikePipeline implements ShouldQueue
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
                ->whereItemType(Status::class)
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

        $activity = FractalService::item($like, new LikeTransformer);

        $url = $status->profile->sharedInbox ?? $status->profile->inbox_url;

        Helpers::sendSignedObject($actor, $url, $activity);
    }
}
