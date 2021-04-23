<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Transformer\Api\Mastodon\v1;

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
