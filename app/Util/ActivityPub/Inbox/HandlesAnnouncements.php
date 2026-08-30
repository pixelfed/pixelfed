<?php

namespace App\Util\ActivityPub\Inbox;

use App\Models\Status;
use App\Services\NotificationService;
use App\Services\ReblogService;
use App\Util\ActivityPub\Helpers;

trait HandlesAnnouncements
{
    public function handleAnnounceActivity(): void
    {
        $actor = $this->validateAndFetchActor($this->payload['actor']);
        $activity = $this->payload['object'];

        if (! $actor || $actor->domain == null) {
            return;
        }

        $parent = Helpers::statusFetch($activity);

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
