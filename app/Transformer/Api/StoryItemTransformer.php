<?php

namespace App\Transformer\Api;

use App\StoryItem;
use League\Fractal;
use Illuminate\Support\Str;

class StoryItemTransformer extends Fractal\TransformerAbstract
{

    public function transform(StoryItem $item): array
    {
        return [
            'id'                        => (string) $item->id,
            'type'                      => $item->type,
            'length'                    => $item->duration != 0 ? $item->duration : 3,
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
