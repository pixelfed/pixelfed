<?php

namespace App\Transformer\ActivityPub\Verb;

use App\Profile;
use League\Fractal;

class DeleteActor extends Fractal\TransformerAbstract
{
    public function transform(Profile $profile)
    {
        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $profile->permalink('#delete'),
            'type' => 'Delete',
            'actor' => $profile->permalink(),
            'to' => [
                'https://www.w3.org/ns/activitystreams#Public',
            ],
            'object' => $profile->permalink(),
        ];
    }
}
