<?php

namespace App\Transformer\Api\Mastodon\v1;

use App\Media;
use League\Fractal;

class MediaTransformer extends Fractal\TransformerAbstract
{
    public function transform(Media $media): array
    {
        return [
            'id'            => (string) $media->id,
            'type'          => lcfirst($media->activityVerb()),
            'url'           => $media->url(),
            'remote_url'    => null,
            'preview_url'   => $media->thumbnailUrl(),
            'text_url'      => null,
            'meta'          => null,
            'description'   => $media->caption
        ];
    }
}