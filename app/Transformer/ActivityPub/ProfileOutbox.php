<?php

namespace App\Transformer\ActivityPub;

use App\Profile;
use League\Fractal;

class ProfileOutbox extends Fractal\TransformerAbstract
{
    public function transform(Profile $profile)
    {
        $count = $profile->statuses()->count();
        $statuses = $profile->statuses()->has('media')->orderBy('id', 'desc')->take(20)->get()->map(function ($i, $k) {
            $item = [
          'id'  => $i->permalink(),
          // TODO: handle other types
          'type'      => 'Create',
          'actor'     => $i->profile->url(),
          'published' => $i->created_at->toISO8601String(),
          'to'        => [
            'https://www.w3.org/ns/activitystreams#Public',
          ],
          'cc' => [
            $i->profile->permalink('/followers'),
          ],
          'object' => [
            'id' => $i->url(),

            // TODO: handle other types
            'type' => 'Note',

            // XXX: CW Title
            'summary'   => null,
            'content'   => $i->rendered ?? $i->caption,
            'inReplyTo' => null,

            // TODO: fix date format
            'published'    => $i->created_at->toAtomString(),
            'url'          => $i->url(),
            'attributedTo' => $i->profile->permalink(),
            'to'           => [
              // TODO: handle proper scope
              'https://www.w3.org/ns/activitystreams#Public',
            ],
            'cc' => [
              // TODO: add cc's
              //"{$notice->getProfile()->getUrl()}/subscribers",
            ],
            'sensitive'        => (bool) $i->is_nsfw,
            'atomUri'          => $i->url(),
            'inReplyToAtomUri' => null,
            'attachment'       => [

              // TODO: support more than 1 attachment
              [
                'type'      => 'Document',
                'mediaType' => $i->firstMedia()->mime,
                'url'       => $i->firstMedia()->url(),
                'name'      => null,
              ],
            ],
            'tag' => [],
          ],
        ];

            return $item;
        });

        return [
          '@context'     => 'https://www.w3.org/ns/activitystreams',
          'id'           => $profile->permalink('/outbox'),
          'type'         => 'OrderedCollection',
          'totalItems'   => $count,
          'orderedItems' => $statuses,
      ];
    }
}
