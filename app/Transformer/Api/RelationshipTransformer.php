<?php

namespace App\Transformer\Api;

use Auth;
use App\{
    FollowRequest,
    Profile
};
use League\Fractal;

class RelationshipTransformer extends Fractal\TransformerAbstract
{
    public function transform(Profile $profile)
    {
        $auth = Auth::check();
        if(!$auth) {
            return [];
        }
        $user = $auth ? Auth::user()->profile : false;
        $requested = false;
        if($user) {
            $requested = FollowRequest::whereFollowerId($user->id)
                ->whereFollowingId($profile->id)
                ->exists();
        }
        return [
            'id' => (string) $profile->id,
            'following' => $auth ? $user->follows($profile) : false,
            'followed_by' => $auth ? $user->followedBy($profile) : false,
            'blocking' => $auth ? $user->blockedIds()->contains($profile->id) : false,
            'muting' => $auth ? $user->mutedIds()->contains($profile->id) : false,
            'muting_notifications' => null,
            'requested' => $requested,
            'domain_blocking' => null,
            'showing_reblogs' => null,
            'endorsed' => false
        ];
    }
}
