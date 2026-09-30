<?php

namespace App\Federation\ActivityBuilders;

use App\Models\Profile;

class BlockActivityBuilder
{
    /**
     * Block of a remote actor by a local actor.
     *
     * @return array<string, mixed>
     */
    public function build(Profile $blocker, Profile $blocked, int $filterId): array
    {
        return [
            ...$this->object($blocker, $blocked, $filterId),
            '@context' => 'https://www.w3.org/ns/activitystreams',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildUndo(Profile $blocker, Profile $blocked, int $filterId): array
    {
        $actorId = $blocker->permalink();

        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $actorId.'#blocks/'.$filterId.'/undo',
            'type' => 'Undo',
            'actor' => $actorId,
            'object' => $this->object($blocker, $blocked, $filterId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function object(Profile $blocker, Profile $blocked, int $filterId): array
    {
        $actorId = $blocker->permalink();

        return [
            'id' => $actorId.'#blocks/'.$filterId,
            'type' => 'Block',
            'actor' => $actorId,
            'object' => $blocked->remote_url,
        ];
    }
}
