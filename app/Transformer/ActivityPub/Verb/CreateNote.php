<?php

namespace App\Transformer\ActivityPub\Verb;

use App\Status;
use League\Fractal;

class CreateNote extends Fractal\TransformerAbstract
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
          'id' => $status->permalink(),
          'type' => 'Create',
          'actor' => $status->profile->permalink(),
          'published' => $status->created_at->toAtomString(),
          'to' => $status->scopeToAudience('to'),
          'cc' => $status->scopeToAudience('cc'),
          'object' => [
	          'id' => $status->url(),

	          // TODO: handle other types
	          'type' => 'Note',

	          // XXX: CW Title
	          'summary'   => null,
	          'content'   => $status->rendered ?? $status->caption,
	          'inReplyTo' => $status->in_reply_to_id ? $status->parent()->url() : null,

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
	          'attachment'       => $status->media->map(function ($media) {
	              return [
	              'type'      => 'Document',
	              'mediaType' => $media->mime,
	              'url'       => $media->url(),
	              'name'      => null,
	            ];
	          })->toArray(),
	          'tag' => [],
	      ]
      ];
    }
}
