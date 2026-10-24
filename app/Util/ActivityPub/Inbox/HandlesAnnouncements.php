<?php

namespace App\Util\ActivityPub\Inbox;

use App\Models\Status;
use App\Services\NotificationService;
use App\Services\ReblogService;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Facades\Log;

trait HandlesAnnouncements
{
    public function handleAnnounceActivity(): void
    {
        $actor = $this->validateAndFetchActor($this->payload['actor']);
        $activity = $this->payload['object'];

        if (! $actor || $actor->domain == null) {
            return;
        }

        try {
            $parent = Helpers::statusFetch($activity);
        } catch (\Exception $e) {
            $context = json_decode($e->getMessage(), true);

            Log::debug('Announce: skipped fetching status', [
                'actor' => $actor->id,
                'actor_domain' => $actor->domain,
                'reason' => is_array($context) ? $context : $e->getMessage(),
                'activity' => $activity,
            ]);

            return;
        }

        if (! $parent) {
            return;
        }

        if ($this->isDomainBlocked($parent->profile_id, $actor->domain)) {
            return;
        }

        if ($this->isUserBlocked($parent->profile_id, $actor->id)) {
            return;
        }

        $status = Status::firstOrCreate([
            'profile_id' => $actor->id,
            'reblog_of_id' => $parent->id,
            'type' => 'share',
            'local' => false,
        ]);

        NotificationService::firstOrCreateNotification($parent->profile_id, $actor->id, 'share', $parent->id, Status::class);

        $parent->reblogs_count = $parent->reblogs_count + 1;
        $parent->save();

        ReblogService::addPostReblog($parent->profile_id, $status->id);
    }
}
