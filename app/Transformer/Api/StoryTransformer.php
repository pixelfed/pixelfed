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
            'name'                      => $story->profile->username,
            'link'                      => $story->profile->url(),
            'lastUpdated'               => $story->updated_at->format('U'),
            'seen'                      => $story->seen(),
        ];
    }

    public function includeItems(Story $story)
    {
        return $this->item($story, new StoryItemTransformer());
    }
}
