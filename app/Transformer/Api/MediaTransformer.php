<?php

namespace App\Transformer\Api;

use App\Media;
use League\Fractal;

class MediaTransformer extends Fractal\TransformerAbstract
{
    public function transform(Media $media)
    {
        return [
            'id'          => $media->id,
            'type'        => 'image',
            'url'         => $media->url(),
            'remote_url'  => null,
            'preview_url' => $media->thumbnailUrl(),
            'text_url'    => null,
            'meta'        => null,
            'description' => null,
        ];
    }
}
