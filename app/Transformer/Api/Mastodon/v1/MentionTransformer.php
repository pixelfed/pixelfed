<?php

namespace App\Transformer\Api\Mastodon\v1;

use App\Profile;
use League\Fractal;

class MentionTransformer extends Fractal\TransformerAbstract
{
    public function transform(Profile $profile)
    {
    	$local = $profile->domain == null;
		$username = $local ? $profile->username : explode('@', substr($profile->username, 1))[0];
        return [
            'id'       => (string) $profile->id,
            'url'      => $profile->url(),
            'username' => $profile->username,
            'acct'     => $username,
        ];
    }
}
