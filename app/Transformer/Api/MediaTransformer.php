<?php

namespace App\Transformer\Api;

use App\Media;
use League\Fractal;

class MediaTransformer extends Fractal\TransformerAbstract
{
    public function transform(Media $media)
    {
        $res = [
            'id'            => (string) $media->id,
            'type'          => $media->activityVerb(),
            'url'           => $media->url() . '?cb=1&_v=' . time(),
            'remote_url'    => null,
            'preview_url'   => $media->thumbnailUrl() . '?cb=1&_v=' . time(),
            'text_url'      => null,
            'meta'          => null,
            'description'   => $media->caption,
            'license'       => $media->license,
            'is_nsfw'       => $media->is_nsfw,
            'orientation'   => $media->orientation,
            'filter_name'   => $media->filter_name,
            'filter_class'  => $media->version == 1 ? $media->filter_class : null,
            'mime'          => $media->mime,
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
