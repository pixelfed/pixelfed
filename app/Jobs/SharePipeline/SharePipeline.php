<?php

namespace App\Jobs\SharePipeline;

use App\Jobs\HomeFeedPipeline\FeedInsertPipeline;
use App\Models\Status;
use App\Services\ActivityPubDeliveryService;
use App\Services\FractalService;
use App\Services\NotificationService;
use App\Services\ReblogService;
use App\Services\StatusService;
use App\Transformer\ActivityPub\Verb\Announce;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

#[DeleteWhenMissingModels]
#[Timeout(60)]
#[Tries(3)]
class SharePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Status $status)
    {
        $this->status = $status;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $status = $this->status;

        if (! $status->reblog_of_id) {
            return;
        }

        $parent = Status::find($status->reblog_of_id);

        if (! $parent) {
            return;
        }

        $actor = $status->profile;
        $target = $parent->profile;

        if (! $actor || ! $target) {
            return;
        }

        $isRemoteShare = $status->uri !== null;

        $isSelfShare = (int) $target->id === (int) $actor->id;

        $targetIsLocal = $target->domain === null;

        ReblogService::addPostReblog($parent->profile_id, $status->id);

        if (Cache::add($this->counterGuardKey($status->id), 1, now()->addDays(30))) {
            Status::whereId($parent->id)->increment('reblogs_count');
            StatusService::del($parent->id);
        }

        if ($targetIsLocal && ! $isSelfShare) {
            NotificationService::firstOrCreateNotification(
                $target->id,
                $actor->id,
                'share',
                $status->reblog_of_id,
                Status::class
            );
        }

        FeedInsertPipeline::dispatch($status->id, $status->profile_id)->onQueue('feed');

        if ($isRemoteShare) {
            return;
        }

        return $this->remoteAnnounceDeliver();
    }

    protected function counterGuardKey($statusId)
    {
        return 'pf:share-pipeline:counted:'.$statusId;
    }

    public function remoteAnnounceDeliver()
    {
        if (config('app.env') !== 'production' || (bool) config_cache('federation.activitypub.enabled') == false) {
            return true;
        }

        $status = $this->status;

        if ($status->uri !== null) {
            return;
        }

        $profile = $status->profile;

        if (! $profile || $profile->domain !== null) {
            return;
        }

        if ($status->scope !== 'public') {
            return;
        }

        $audience = $profile->getAudienceInbox();

        if (empty($audience)) {
            return;
        }

        $activity = FractalService::item($status, new Announce);

        ActivityPubDeliveryService::pool($profile, $audience, $activity);
    }
}
