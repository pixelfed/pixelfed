<?php

namespace App\Transformer\Api;

use App\StoryItem;
use League\Fractal;
use Illuminate\Support\Str;

class StoryItemTransformer extends Fractal\TransformerAbstract
{

    public function transform(StoryItem $item)
    {
        return [
            'id'                        => (string) Str::uuid(),
            'type'                      => $item->type,
            'length'                    => $item->duration,
            'src'                       => $item->url(),
            'preview'                   => null,
            'link'                      => null,
            'linkText'                  => null,
            'time'                      => $item->updated_at->format('U'),
            'seen'                      => $item->story->seen(),
        ];
    }

}
