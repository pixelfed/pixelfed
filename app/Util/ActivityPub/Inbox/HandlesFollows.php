<?php

namespace App\Util\ActivityPub\Inbox;

use App\Jobs\FollowPipeline\FollowPipeline;
use App\Models\Follower;
use App\Models\FollowRequest;
use App\Services\FollowerService;
use App\Services\RelationshipService;
use App\Util\ActivityPub\Helpers;

trait HandlesFollows
{
    public function handleFollowActivity(): void
    {
        $actor = $this->validateAndFetchActor($this->payload['actor']);
        $target = $this->validateAndFetchActor($this->payload['object']);

        if (! $actor || ! $target) {
            return;
        }

        if ($target->moved_to_profile_id) {
            return;
        }

        if ($actor->domain == null || $target->domain !== null) {
            return;
        }

        if ($this->isDomainBlocked($target->id, $actor->domain)) {
            return;
        }

        if (
            Follower::whereProfileId($actor->id)
                ->whereFollowingId($target->id)
                ->exists() ||
            FollowRequest::whereFollowerId($actor->id)
                ->whereFollowingId($target->id)
                ->exists()
        ) {
            return;
        }

        if ($this->isUserBlocked($target->id, $actor->id)) {
            return;
        }

        if ($target->is_private == true) {
            FollowRequest::updateOrCreate([
                'follower_id' => $actor->id,
                'following_id' => $target->id,
            ], [
                'activity' => collect($this->payload)->only(['id', 'actor', 'object', 'type'])->toArray(),
            ]);
        } else {
            $follower = new Follower;
            $follower->profile_id = $actor->id;
            $follower->following_id = $target->id;
            $follower->local_profile = empty($actor->domain);
            $follower->save();

            FollowPipeline::dispatch($follower);
            FollowerService::add($actor->id, $target->id);

            $accept = [
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id' => $target->permalink().'#accepts/follows/'.$follower->id,
                'type' => 'Accept',
                'actor' => $target->permalink(),
                'object' => [
                    'id' => $this->payload['id'],
                    'actor' => $actor->permalink(),
                    'type' => 'Follow',
                    'object' => $target->permalink(),
                ],
            ];
            Helpers::sendSignedObject($target, $actor->inbox_url, $accept);
            $this->clearProfileCache($target->id, $actor->id);
        }

        RelationshipService::refresh($actor->id, $target->id);
        $this->clearAccountCache($actor->id, $target->id);
    }

    public function handleAcceptActivity(): void
    {
        $actor = $this->payload['object']['actor'];
        $obj = $this->payload['object']['object'];
        $type = $this->payload['object']['type'];

        if ($type !== 'Follow') {
            return;
        }

        $actor = Helpers::validateLocalUrl($actor);
        $target = Helpers::validateUrl($obj);

        if (! $actor || ! $target) {
            return;
        }

        $actor = Helpers::profileFetch($actor);
        $target = Helpers::profileFetch($target);

        if (! $actor || ! $target) {
            return;
        }

        if ($this->isDomainBlocked($target->id, $actor->domain)) {
            return;
        }

        $request = FollowRequest::whereFollowerId($actor->id)
            ->whereFollowingId($target->id)
            ->whereIsRejected(false)
            ->first();

        if (! $request) {
            return;
        }

        $follower = Follower::firstOrCreate([
            'profile_id' => $actor->id,
            'following_id' => $target->id,
        ]);

        FollowPipeline::dispatch($follower)->onQueue('high');
        RelationshipService::refresh($actor->id, $target->id);
        $this->clearProfileCache($actor->id, $target->id);
        $this->clearAccountCache($actor->id, $target->id);
        RelationshipService::get($actor->id, $target->id);
        $request->delete();
    }

    public function handleRejectActivity(): void
    {
        $actorUrl = $this->payload['actor'];
        $obj = $this->payload['object'];
        $profileUrl = $obj['actor'];

        if (! Helpers::validateUrl($actorUrl) || ! Helpers::validateLocalUrl($profileUrl)) {
            return;
        }

        $actor = Helpers::profileFetch($actorUrl);
        $profile = Helpers::profileFetch($profileUrl);

        FollowRequest::whereFollowerId($profile->id)->whereFollowingId($actor->id)->forceDelete();
        RelationshipService::refresh($actor->id, $profile->id);
    }
}
