<?php

namespace App\Jobs\SharePipeline;

use App\Jobs\HomeFeedPipeline\FeedRemovePipeline;
use App\Models\Notification;
use App\Models\Status;
use App\Services\ActivityPubDeliveryService;
use App\Services\FractalService;
use App\Services\ReblogService;
use App\Services\StatusService;
use App\Transformer\ActivityPub\Verb\UndoAnnounce;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

#[DeleteWhenMissingModels]
class UndoSharePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;

    public function __construct(Status $status)
    {
        $this->status = $status;
    }

    public function handle()
    {
        $status = $this->status;
        $actor = $status->profile;
        $parent = Status::find($status->reblog_of_id);

        FeedRemovePipeline::dispatch($status->id, $status->profile_id)->onQueue('feed');

        if ($parent) {
            $target = $parent->profile_id;
            ReblogService::removePostReblog($parent->profile_id, $status->id);

            if ($parent->reblogs_count > 0) {
                $parent->reblogs_count = $parent->reblogs_count - 1;
                $parent->save();
                StatusService::del($parent->id);
            }

            $notification = Notification::whereProfileId($target)
                ->whereActorId($status->profile_id)
                ->whereAction('share')
                ->whereItemId($status->reblog_of_id)
                ->whereItemType(Status::class)
                ->first();

            if ($notification) {
                $notification->forceDelete();
            }
        }

        if ($status->uri != null) {
            return;
        }

        if (config('app.env') !== 'production' || (bool) config_cache('federation.activitypub.enabled') == false) {
            return $status->delete();
        } else {
            return $this->remoteAnnounceDeliver();
        }
    }

    public function remoteAnnounceDeliver()
    {
        if (config('app.env') !== 'production' || (bool) config_cache('federation.activitypub.enabled') == false) {
            $this->status->delete();

            return 1;
        }

        $status = $this->status;
        $profile = $status->profile;

        $activity = FractalService::item($status, new UndoAnnounce);

        $audience = $status->profile->getAudienceInbox();

        if (empty($audience) || $status->scope != 'public') {
            return 1;
        }

        ActivityPubDeliveryService::pool($profile, $audience, $activity);

        $status->delete();

        return 1;
    }
}
