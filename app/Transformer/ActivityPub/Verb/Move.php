<?php

namespace App\Transformer\ActivityPub\Verb;

use App\Models\ProfileMigration;
use League\Fractal;

class Move extends Fractal\TransformerAbstract
{
    public function transform(ProfileMigration $migration)
    {
        $objUrl = $migration->profile->permalink();
        $id = $migration->profile->permalink('#moves/'.$migration->id);
        $to = $migration->profile->permalink('/followers');

        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $id,
            'actor' => $objUrl,
            'type' => 'Move',
            'object' => $objUrl,
            'target' => $migration->target->permalink(),
            'to' => $to,
        ];
    }
}
