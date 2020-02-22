<?php

namespace App\Transformer\ActivityPub;

use App\Status;
use League\Fractal;

class StatusTransformer extends Fractal\TransformerAbstract
{
    public function transform(Status $status)
    {
        return [
          '@context' => [
            'https://www.w3.org/ns/activitystreams',
            'https://w3id.org/security/v1',
            [
              'manuallyApprovesFollowers' => 'as:manuallyApprovesFollowers',
              'featured'                  => [
                'https://pixelfed.org/ns#featured' => ['@type' => '@id'],
              ],
            ],
          ],
          'id' => $status->url(),

          // TODO: handle other types
          'type' => 'Note',

          // XXX: CW Title
          'summary'   => null,
          'content'   => $status->rendered ?? $status->caption,
          'inReplyTo' => null,

          // TODO: fix date format
          'published'    => $status->created_at->toAtomString(),
          'url'          => $status->url(),
          'attributedTo' => $status->profile->permalink(),
          'to'           => [
            // TODO: handle proper scope
            'https://www.w3.org/ns/activitystreams#Public',
          ],
          'cc' => [
            // TODO: add cc's
            $status->profile->permalink('/followers'),
          ],
          'sensitive'        => (bool) $status->is_nsfw,
          'atomUri'          => $status->url(),
          'inReplyToAtomUri' => null,
          'attachment'       => $status->media->map(function ($media) {
              return [
              'type'      => 'Document',
              'mediaType' => $media->mime,
              'url'       => $media->url(),
              'name'      => $media->caption
              ];
          }),
          'tag' => [],
          'location' => $status->place_id ? [
              'type' => 'Place',
              'name' => $status->place->name,
              'longitude' => $status->place->long,
              'latitude' => $status->place->lat,
              'country' => $status->place->country
            ] : null,
        ];
    }
}
