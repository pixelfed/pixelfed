<?php

namespace App\Util\ActivityPub\Inbox;

use App\Jobs\LikePipeline\LikePipeline;
use App\Like;
use App\Util\ActivityPub\Helpers;

trait HandlesLikes
{
    public function handleLikeActivity(): void
    {
        $actor = $this->payload['actor'];

        if (! Helpers::validateUrl($actor)) {
            return;
        }

        $profile = $this->validateAndFetchActor($actor);
        $obj = $this->payload['object'];

        if (! Helpers::validateUrl($obj)) {
            return;
        }

        $status = Helpers::statusFirstOrFetch($obj);

        if (! $status || ! $profile) {
            return;
        }

        if ($this->isDomainBlocked($status->profile_id, $profile->domain)) {
            return;
        }

        if ($this->isUserBlocked($status->profile_id, $profile->id)) {
            return;
        }

        $like = Like::firstOrCreate([
            'profile_id' => $profile->id,
            'status_id' => $status->id,
        ]);

        if ($like->wasRecentlyCreated == true) {
            $status->likes_count = $status->likes_count + 1;
            $status->save();
            LikePipeline::dispatch($like);
        }
    }
}
