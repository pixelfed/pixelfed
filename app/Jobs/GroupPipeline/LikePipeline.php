<?php

namespace App\Jobs\GroupPipeline;

use App\Models\Like;
use App\Models\Notification;
use App\Models\Status;
use App\Services\FractalService;
use App\Services\NotificationService;
use App\Services\StatusService;
use App\Transformer\ActivityPub\Verb\Like as LikeTransformer;
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
class LikePipeline implements ShouldQueue
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

        StatusService::refresh($status->id);

        if ($status->url && $actor->domain == null) {
            return $this->remoteLikeDeliver();
        }

        $exists = Notification::whereProfileId($status->profile_id)
            ->whereActorId($actor->id)
            ->whereAction('group:like')
            ->whereItemId($status->id)
            ->whereItemType(Status::class)
            ->count();

        if ($actor->id === $status->profile_id || $exists !== 0) {
            return true;
        }

        try {
            NotificationService::createNotification($status->profile_id, $actor->id, 'group:like', $status->id, Status::class);

        } catch (\Exception $e) {
        }
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
