<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Transformer\Api;

use App\Instance;
use League\Fractal;

class InstanceTransformer extends Fractal\TransformerAbstract
{
    public function transform(Instance $instance)
    {
        return [
            'uri'               => $instance->url,
            'title'             => null,
            'description'       => null,
            'email'             => null,
            'version'           => null,
            'thumbnail'         => null,
            'urls'              => [],
            'stats'             => [],
            'languages'         => null,
            'contact_account'   => null
        ];
    }
}
