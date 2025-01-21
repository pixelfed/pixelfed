<?php

namespace App\Transformer\ActivityPub;

use App\Services\MediaService;
use App\Services\StatusService;
use App\Status;
use App\Util\Lexer\Autolink;
use League\Fractal;

class StatusTransformer extends Fractal\TransformerAbstract
{
    public function transform(Status $status)
    {
        $content = $status->caption ? nl2br(Autolink::create()->autolink($status->caption)) : '';

        $inReplyTo = null;

        if ($status->in_reply_to_id) {
            $reply = StatusService::get($status->in_reply_to_id, true);
            if ($reply && isset($reply['url'])) {
                $inReplyTo = $reply['url'];
            }
        }

        return [
            '@context' => [
                'https://www.w3.org/ns/activitystreams',
                'https://w3id.org/security/v1',
                [
                    'manuallyApprovesFollowers' => 'as:manuallyApprovesFollowers',
                    'featured' => [
                        'https://pixelfed.org/ns#featured' => ['@type' => '@id'],
                    ],
                ],
            ],
            'id' => $status->url(),
            'type' => 'Note',
            'summary' => null,
            'content' => $content,
            'inReplyTo' => $inReplyTo,
            'published' => $status->created_at->toAtomString(),
            'url' => $status->url(),
            'attributedTo' => $status->profile->permalink(),
            'to' => [
                'https://www.w3.org/ns/activitystreams#Public',
            ],
            'cc' => [
                $status->profile->permalink('/followers'),
            ],
            'sensitive' => (bool) $status->is_nsfw,
            'attachment' => MediaService::activitypub($status->id),
            'tag' => [],
            'location' => $status->place_id ? [
                'type' => 'Place',
                'name' => $status->place->name,
                'longitude' => $status->place->long,
                'latitude' => $status->place->lat,
                'country' => $status->place->country,
            ] : null,
        ];
    }
}
