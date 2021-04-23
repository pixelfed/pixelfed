<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Transformer\Api;

use App\StoryItem;
use League\Fractal;
use Illuminate\Support\Str;

class StoryItemTransformer extends Fractal\TransformerAbstract
{

    public function transform(StoryItem $item)
    {
        return [
            'id'                        => (string) $item->id,
            'type'                      => $item->type,
            'length'                    => 10,
            'src'                       => $item->url(),
            'preview'                   => null,
            'link'                      => null,
            'linkText'                  => null,
            'time'                      => $item->created_at->format('U'),
            'expires_at'                => $item->created_at->addHours(24)->format('U'),
            'seen'                      => $item->story->seen(),
        ];
    }

}
