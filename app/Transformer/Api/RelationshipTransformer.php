<?php

namespace App\Transformer\Api;

use App\Profile;
use League\Fractal;

class RelationshipTransformer extends Fractal\TransformerAbstract
{
    public function transform(Profile $profile)
    {
        return [
            'id' => $profile->id,
            'following' => null,
            'followed_by' => null,
            'blocking' => null,
            'muting' => null,
            'muting_notifications' => null,
            'requested' => null,
            'domain_blocking' => null,
            'showing_reblogs' => null,
            'endorsed' => null
        ];
    }
}
