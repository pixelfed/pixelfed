<?php

namespace App\Transformer\Api;

use Auth;
use App\Profile;
use League\Fractal;

class RelationshipTransformer extends Fractal\TransformerAbstract
{
    public function transform(Profile $profile)
    {
        $user = Auth::user()->profile;
        return [
            'id' => (string) $profile->id,
            'following' => $user->follows($profile),
            'followed_by' => $user->followedBy($profile),
            'blocking' => $user->blockedIds()->contains($profile->id),
            'muting' => $user->mutedIds()->contains($profile->id),
            'muting_notifications' => null,
            'requested' => null,
            'domain_blocking' => null,
            'showing_reblogs' => null,
            'endorsed' => false
        ];
    }
}
