<?php

namespace App\Transformer\Api;

use App\Media;
use League\Fractal;
use URL;

class MediaDraftTransformer extends Fractal\TransformerAbstract
{
    public function transform(Media $media)
    {
        return [
            'id'            => (string) $media->id,
            'type'          => $media->activityVerb(),
            'url'           => $media->url(),
            'remote_url'    => null,
            'preview_url'   => $media->thumbnailUrl(),
            'text_url'      => null,
            'meta'          => null,
            'description'   => $media->caption,
            'license'       => $media->license,
            'is_nsfw'       => $media->is_nsfw,
            'orientation'   => $media->orientation,
            'filter_name'   => $media->filter_name,
            'filter_class'  => $media->filter_class,
            'mime'          => $media->mime,
        ];
    }
}
