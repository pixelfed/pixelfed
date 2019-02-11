<?php

namespace App\Transformer\Api;

use App\Profile;
use League\Fractal;

class MentionTransformer extends Fractal\TransformerAbstract
{
    public function transform(Profile $profile)
    {
        return [
            'id'       => (string) $profile->id,
            'url'      => $profile->url(),
            'username' => $profile->username,
            'acct'     => $profile->username,
        ];
    }
}
