<?php

namespace App\Transformer\ActivityPub\Verb;

use App\Models\Profile;
use League\Fractal;

class DeleteActor extends Fractal\TransformerAbstract
{
    public function transform(Profile $profile)
    {
        $actorId = $profile->permalink();

        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $actorId.'#delete',
            'type' => 'Delete',
            'actor' => $actorId,
            'object' => [
                'id' => $actorId,
                'type' => 'Person',
            ],
        ];
    }
}
