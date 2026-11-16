<?php

namespace App\Federation\ActivityBuilders;

use App\Models\Profile;

class FollowActivityBuilder
{
    public function build(Profile $follower, Profile $target): array
    {
        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            ...$this->object($follower, $target),
        ];
    }

    public function buildUndo(Profile $follower, Profile $target): array
    {
        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $follower->permalink('#follow/'.$target->id.'/undo'),
            'type' => 'Undo',
            'actor' => $follower->permalink(),
            'object' => $this->object($follower, $target),
        ];
    }

    protected function object(Profile $follower, Profile $target): array
    {
        return [
            'id' => $follower->permalink('#follows/'.$target->id),
            'type' => 'Follow',
            'actor' => $follower->permalink(),
            'object' => $target->permalink(),
        ];
    }
}
