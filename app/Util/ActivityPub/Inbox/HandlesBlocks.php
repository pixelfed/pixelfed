<?php

namespace App\Util\ActivityPub\Inbox;

use App\Models\Profile;
use App\Services\BlockSyncService;
use App\Services\FollowersSyncService;

trait HandlesBlocks
{
    public function handleBlockActivity(): void
    {
        if (! BlockSyncService::receiving()) {
            return;
        }

        $actorUrl = BlockSyncService::idOf($this->payload['actor'] ?? null);
        $objectUrl = BlockSyncService::idOf($this->payload['object'] ?? null);

        if (! $actorUrl || ! $objectUrl) {
            return;
        }

        $target = FollowersSyncService::resolveLocalActor($objectUrl);

        if (! $target) {
            return;
        }

        $actor = $this->validateAndFetchActor($actorUrl);

        if (! $actor || $actor->domain === null || ! $this->signedFromActorAuthority($actor)) {
            return;
        }

        BlockSyncService::applyRemoteBlock($actor, $target);
    }

    protected function handleUndoBlock(Profile $profile, array $obj): void
    {
        if (! BlockSyncService::receiving() || $profile->domain === null) {
            return;
        }

        $blockActor = BlockSyncService::idOf($obj['actor'] ?? null);

        if ($blockActor !== null && $blockActor !== $profile->remote_url) {
            return;
        }

        $objectUrl = BlockSyncService::idOf($obj['object'] ?? null);

        if (! $objectUrl) {
            return;
        }

        $target = FollowersSyncService::resolveLocalActor($objectUrl);

        if (! $target || ! $this->signedFromActorAuthority($profile)) {
            return;
        }

        BlockSyncService::removeRemoteBlock($profile, $target);
    }

    protected function signedFromActorAuthority(Profile $actor): bool
    {
        $signer = $this->signingProfile();

        if (! $signer || $signer->domain === null) {
            return false;
        }

        $authority = FollowersSyncService::authority($actor->remote_url);

        return $authority !== null
            && FollowersSyncService::authority($signer->remote_url) === $authority;
    }
}
