<?php

namespace App\Util\ActivityPub\Inbox;

use App\Follower;
use App\FollowRequest;
use App\Jobs\HomeFeedPipeline\FeedRemoveRemotePipeline;
use App\Like;
use App\Profile;
use App\Services\FollowerService;
use App\Services\ReblogService;
use App\Services\RelationshipService;
use App\Services\StatusService;
use App\Status;
use App\Util\ActivityPub\Helpers;

trait HandlesUndos
{
    public function handleUndoActivity(): void
    {
        $actor = $this->payload['actor'];
        $profile = $this->validateAndFetchActor($actor);
        $obj = $this->payload['object'];

        if (! $profile) {
            return;
        }

        if (! $obj || ! is_array($obj) || ! isset($obj['type'])) {
            return;
        }

        match ($obj['type']) {
            'Accept' => null,
            'Announce' => $this->handleUndoAnnounce($profile, $obj),
            'Block' => null,
            'Follow' => $this->handleUndoFollow($profile, $obj),
            'Like' => $this->handleUndoLike($profile, $obj),
            default => null,
        };
    }

    protected function handleUndoAnnounce(Profile $profile, array $obj): void
    {
        if (is_array($obj) && isset($obj['object'])) {
            $obj = $obj['object'];
        }

        if (! is_string($obj)) {
            return;
        }

        if (Helpers::validateLocalUrl($obj)) {
            $parsedId = last(explode('/', $obj));
            $status = Status::find($parsedId);
        } else {
            $status = Status::whereUri($obj)->first();
        }

        if (! $status) {
            return;
        }

        if ($this->isDomainBlocked($status->profile_id, $profile->domain)) {
            return;
        }

        FeedRemoveRemotePipeline::dispatch($status->id, $status->profile_id)->onQueue('feed');

        Status::whereProfileId($profile->id)
            ->whereReblogOfId($status->id)
            ->forceDelete();

        if ($status->reblogs_count) {
            $status->reblogs_count = $status->reblogs_count - 1;
            $status->saveQuietly();
        }

        ReblogService::removePostReblog($profile->id, $status->id);

        $this->deleteNotifications([
            'profile_id' => $status->profile_id,
            'actor_id' => $profile->id,
            'action' => 'share',
            'item_id' => $status->id,
            'item_type' => Status::class,
        ]);
    }

    protected function handleUndoFollow(Profile $profile, array $obj): void
    {
        $following = $this->validateAndFetchActor($obj['object']);

        if (! $following) {
            return;
        }

        if ($this->isDomainBlocked($following->id, $profile->domain)) {
            return;
        }

        Follower::whereProfileId($profile->id)
            ->whereFollowingId($following->id)
            ->delete();

        FollowRequest::whereFollowingId($following->id)
            ->whereFollowerId($profile->id)
            ->forceDelete();

        $this->deleteNotifications([
            'profile_id' => $following->id,
            'actor_id' => $profile->id,
            'action' => 'follow',
            'item_id' => $following->id,
            'item_type' => Profile::class,
        ]);

        FollowerService::remove($profile->id, $following->id);
        RelationshipService::refresh($following->id, $profile->id);
        $this->clearAccountCache($profile->id, $following->id);
    }

    protected function handleUndoLike(Profile $profile, array $obj): void
    {
        $objectUri = $obj['object'];

        if (! is_string($objectUri)) {
            if (is_array($objectUri) && isset($objectUri['id']) && is_string($objectUri['id'])) {
                $objectUri = $objectUri['id'];
            } else {
                return;
            }
        }

        $status = Helpers::statusFirstOrFetch($objectUri);

        if (! $status) {
            return;
        }

        if ($this->isDomainBlocked($status->profile_id, $profile->domain)) {
            return;
        }

        $deleted = Like::whereProfileId($profile->id)
            ->whereStatusId($status->id)
            ->forceDelete();

        if ($deleted > 0 && $status->likes_count > 0) {
            $status->likes_count = $status->likes_count - 1;
            $status->saveQuietly();
            StatusService::del($status->id);
        }

        $this->deleteNotifications([
            'profile_id' => $status->profile_id,
            'actor_id' => $profile->id,
            'action' => 'like',
            'item_id' => $status->id,
            'item_type' => Status::class,
        ]);
    }
}
