<?php

namespace App\Util\ActivityPub\Inbox;

use App\Jobs\DeletePipeline\DeleteRemoteProfilePipeline;
use App\Jobs\HomeFeedPipeline\FeedRemoveRemotePipeline;
use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Jobs\StoryPipeline\StoryExpire;
use App\Models\Notification;
use App\Models\Profile;
use App\Models\Status;
use App\Models\Story;
use App\Util\ActivityPub\Helpers;

trait HandlesDeletes
{
    public function handleDeleteActivity(): void
    {
        if (! $this->payloadHasKeys(['actor', 'object'])) {
            return;
        }

        $actor = $this->payload['actor'];
        $obj = $this->payload['object'];

        if (is_string($obj) && $actor == $obj && Helpers::validateUrl($obj)) {
            $this->handleDeleteActorSelf($obj);

            return;
        }

        $this->handleDeleteObject($actor, $obj);
    }

    /**
     * Handle self-deletion where actor == object (account deletion).
     */
    protected function handleDeleteActorSelf(string $actorUrl): void
    {
        $profile = Profile::whereRemoteUrl($actorUrl)->first();

        if (! $profile || $profile->private_key != null) {
            return;
        }

        DeleteRemoteProfilePipeline::dispatch($profile)->onQueue('inbox');
    }

    /**
     * Handle deletion of a specific object (Person, Tombstone, Story).
     */
    protected function handleDeleteObject(string $actor, mixed $obj): void
    {
        if (! isset(
            $obj['id'],
            $this->payload['object'],
            $this->payload['object']['id'],
            $this->payload['object']['type']
        )) {
            return;
        }

        $type = $this->payload['object']['type'];
        $typeCheck = in_array($type, ['Person', 'Tombstone', 'Story']);

        if (! Helpers::validateUrl($actor) || ! Helpers::validateUrl($obj['id']) || ! $typeCheck) {
            return;
        }

        if (! $this->hostsMatch($obj['id'], $actor)) {
            return;
        }

        $id = $this->payload['object']['id'];

        match ($type) {
            'Person' => $this->handleDeletePerson($actor),
            'Tombstone' => $this->handleDeleteTombstone($actor, $id),
            'Story' => $this->handleDeleteStory($id),
            default => null,
        };
    }

    protected function handleDeletePerson(string $actorUrl): void
    {
        $profile = Profile::whereRemoteUrl($actorUrl)->first();

        if (! $profile || $profile->private_key != null) {
            return;
        }

        Notification::whereActorId($profile->id)
            ->chunkById(100, function ($notifications) {
                foreach ($notifications as $notification) {
                    $notification->forceDelete();
                }
            });

        DeleteRemoteProfilePipeline::dispatch($profile)->onQueue('inbox');
    }

    protected function handleDeleteTombstone(string $actorUrl, string $objectId): void
    {
        $profile = Profile::whereRemoteUrl($actorUrl)->first();

        if (! $profile || $profile->private_key != null) {
            return;
        }

        $status = Status::where('object_url', $objectId)->first();
        if (! $status) {
            $status = Status::where('url', $objectId)->first();
            if (! $status) {
                return;
            }
        }

        if ($status->profile_id != $profile->id) {
            return;
        }

        $this->deleteNotifications([
            'actor_id' => $status->profile_id,
            'item_id' => $status->id,
            'item_type' => Status::class,
        ]);

        if ($status->scope && in_array($status->scope, ['public', 'unlisted', 'private'])) {
            if ($status->type && ! in_array($status->type, ['story:reaction', 'story:reply', 'reply'])) {
                FeedRemoveRemotePipeline::dispatch($status->id, $status->profile_id)->onQueue('feed');
            }
        }

        RemoteStatusDelete::dispatch($status)->onQueue('high');
    }

    protected function handleDeleteStory(string $objectId): void
    {
        $story = Story::whereObjectId($objectId)->first();

        if ($story) {
            StoryExpire::dispatch($story)->onQueue('story');
        }
    }
}
