<?php

namespace App\Jobs\GroupPipeline;

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
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

#[DeleteWhenMissingModels]
#[Timeout(5)]
#[Tries(1)]
class UnlikePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $like;

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

        $count = $status->likes_count > 1 ? $status->likes_count : $status->likes()->count();
        $status->likes_count = $count - 1;
        $status->save();

        StatusService::del($status->id);

        if ($actor->id !== $status->profile_id && $status->url && $actor->domain == null) {
            $this->remoteLikeDeliver();
        }

        $exists = Notification::whereProfileId($status->profile_id)
            ->whereActorId($actor->id)
            ->whereAction('group:like')
            ->whereItemId($status->id)
            ->whereItemType(Status::class)
            ->first();

        if ($exists) {
            $exists->delete();
        }

        $like = Like::whereProfileId($actor->id)->whereStatusId($status->id)->first();

        if (! $like) {
            return;
        }

        $like->forceDelete();

    }

    public function remoteLikeDeliver()
    {
        $like = $this->like;
        $status = $this->like->status;
        $actor = $this->like->actor;

        $activity = FractalService::item($like, new LikeTransformer);

        $url = $status->profile->sharedInbox ?? $status->profile->inbox_url;

        Helpers::sendSignedObject($actor, $url, $activity);
    }
}
