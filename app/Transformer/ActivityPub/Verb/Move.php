<?php

namespace App\Transformer\ActivityPub\Verb;

use App\Models\ProfileMigration;
use League\Fractal;

class Move extends Fractal\TransformerAbstract
{
    public function transform(ProfileMigration $migration)
    {
        $objUrl = $migration->target->permalink();
        $id = $migration->target->permalink('#moves/'.$migration->id);
        $to = $migration->target->permalink('/followers');

        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $id,
            'actor' => $objUrl,
            'type' => 'Move',
            'object' => $objUrl,
            'target' => $migration->profile->permalink(),
            'to' => $to,
        ];
    }
}
