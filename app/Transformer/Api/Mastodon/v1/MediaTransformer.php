<?php

namespace App\Transformer\Api\Mastodon\v1;

use App\Media;
use League\Fractal;

class MediaTransformer extends Fractal\TransformerAbstract
{
    public function transform(Media $media)
    {
        $res = [
            'id'            => (string) $media->id,
            'type'          => lcfirst($media->activityVerb()),
            'url'           => $media->url(),
            'remote_url'    => null,
            'preview_url'   => $media->thumbnailUrl(),
            'text_url'      => null,
            'meta'          => null,
            'description'   => $media->caption,
            'blurhash'      => $media->blurhash
        ];

        if($media->width && $media->height) {
            $res['meta'] = [
                'focus' => [
                    'x' => 0,
                    'y' => 0
                ],
                'original' => [
                    'width' => $media->width,
                    'height' => $media->height,
                    'size' => "{$media->width}x{$media->height}",
                    'aspect' => $media->width / $media->height
                ]
            ];
        }

        return $res;
    }
}