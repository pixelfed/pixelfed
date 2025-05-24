<?php

namespace App\Transformer\Api;

use App\FollowRequest;
use App\Models\UserDomainBlock;
use App\Profile;
use Auth;
use League\Fractal;

class RelationshipTransformer extends Fractal\TransformerAbstract
{
    public function transform(Profile $profile)
    {
        $auth = Auth::check();
        if (! $auth) {
            return [];
        }
        $user = $auth ? Auth::user()->profile : false;
        $requested = false;
        $domainBlocking = false;
        if ($user) {
            $requested = FollowRequest::whereFollowerId($user->id)
                ->whereFollowingId($profile->id)
                ->exists();

            if ($profile->domain) {
                $domainBlocking = UserDomainBlock::whereProfileId($user->id)
                    ->whereDomain($profile->domain)
                    ->exists();
            }
        }

        return [
            'id' => (string) $profile->id,
            'following' => $auth ? $user->follows($profile) : false,
            'followed_by' => $auth ? $user->followedBy($profile) : false,
            'blocking' => $auth ? $user->blockedIds()->contains($profile->id) : false,
            'muting' => $auth ? $user->mutedIds()->contains($profile->id) : false,
            'muting_notifications' => false,
            'requested' => $requested,
            'domain_blocking' => $domainBlocking,
            'showing_reblogs' => false,
            'endorsed' => false,
        ];
    }
}
