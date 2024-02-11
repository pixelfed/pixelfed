<?php

namespace App\Transformer\Api;

use App\StoryItem;
use League\Fractal;

class StoryItemTransformer extends Fractal\TransformerAbstract
{
    public function transform(StoryItem $item)
    {
        return [
            'id' => (string) $item->id,
            'type' => $item->type,
            'length' => 10,
            'src' => $item->url(),
            'preview' => null,
            'link' => null,
            'linkText' => null,
            'time' => $item->created_at->format('U'),
            'expires_at' => $item->created_at->addHours(24)->format('U'),
            'seen' => $item->story->seen(),
        ];
    }
}
