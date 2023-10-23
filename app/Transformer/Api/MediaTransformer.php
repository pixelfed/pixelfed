<?php

namespace App\Transformer\Api;

use App\Media;
use League\Fractal;
use Storage;

class MediaTransformer extends Fractal\TransformerAbstract
{
    public function transform(Media $media)
    {
        $res = [
            'id'            => (string) $media->id,
            'type'          => strtolower($media->activityVerb()),
            'url'           => $media->url(),
            'remote_url'    => null,
            'preview_url'   => $media->thumbnailUrl(),
            'optimized_url' => $media->optimized_url,
            'text_url'      => null,
            'meta'          => null,
            'description'   => $media->caption,
            'license'       => $media->getLicense(),
            'is_nsfw'       => $media->is_nsfw,
            'orientation'   => $media->orientation,
            'filter_name'   => $media->filter_name,
            'filter_class'  => $media->version == 1 ? $media->filter_class : null,
            'mime'          => $media->mime,
            'blurhash'      => $media->blurhash ?? 'U4Rfzst8?bt7ogayj[j[~pfQ9Goe%Mj[WBay'
        ];

        if(config('media.hls.enabled') && $media->hls_transcoded_at != null && $media->hls_path) {
            $res['hls_manifest'] = url(Storage::url($media->hls_path));
        }

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
