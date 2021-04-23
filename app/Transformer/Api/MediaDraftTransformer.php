<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

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
            'url'           => $url,
            'remote_url'    => null,
            'preview_url'   => $url,
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
