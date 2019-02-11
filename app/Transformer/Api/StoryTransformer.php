<?php

namespace App\Transformer\Api;

use App\Story;
use League\Fractal;

class StoryTransformer extends Fractal\TransformerAbstract
{
    protected $defaultIncludes = [
        'items',
    ];

    public function transform(Story $story)
    {
        return [
            'id'                        => (string) $story->id,
            'photo'                     => $story->profile->avatarUrl(),
            'name'                      => '',
            'link'                      => '',
            'lastUpdated'               => $story->updated_at->format('U'),
            'seen'                      => $story->seen(),
            'items'                     => [],
        ];
    }

    public function includeItems(Story $story)
    {
        $items = $story->items;

        return $this->collection($items, new StoryItemTransformer());
    }

}
