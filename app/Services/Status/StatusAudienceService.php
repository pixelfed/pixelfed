<?php

namespace App\Services\Status;

use App\Models\Profile;
use App\Models\Status;

class StatusAudienceService
{
    /**
     * Followers' inboxes, plus mentioned remote accounts and the remote
     * author being replied to.
     *
     * @return array<int, string>
     */
    public static function inboxes(Status $status, ?Profile $profile = null): array
    {
        $profile ??= $status->profile;

        if (! $profile) {
            return [];
        }

        $audience = $profile->getAudienceInbox();

        $mentions = $status->mentions
            ->filter(fn($m) => $m->domain !== null)
            ->map(fn($m) => $m->sharedInbox ?? $m->inbox_url)
            ->values()
            ->toArray();

        $parentInbox = [];

        if ($status->in_reply_to_profile_id) {
            $parent = Profile::find($status->in_reply_to_profile_id);

            if ($parent && $parent->domain !== null) {
                $parentInbox = [$parent->sharedInbox ?? $parent->inbox_url];
            }
        }

        return array_values(array_unique(array_filter(
            array_merge($audience, $mentions, $parentInbox),
            fn($inbox) => is_string($inbox) && trim($inbox) !== ''
        )));
    }
}
