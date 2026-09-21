<?php

namespace App\Federation\ActivityBuilders;

use App\Models\Profile;

class AccountDeleteActivityBuilder
{
    /**
     * Delete of the actor itself. The object is the bare actor id, which is
     * the shape Mastodon sends and the one every implementation that handles
     * account deletion understands.
     *
     * @return array<string, mixed>
     */
    public function build(Profile $profile): array
    {
        $actorId = $profile->permalink();

        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $actorId.'#delete',
            'type' => 'Delete',
            'actor' => $actorId,
            'to' => ['https://www.w3.org/ns/activitystreams#Public'],
            'object' => $actorId,
        ];
    }
}
