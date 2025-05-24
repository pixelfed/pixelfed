<?php

namespace App\Transformer\ActivityPub\Verb;

use App\Models\CustomEmoji;
use App\Services\MediaService;
use App\Status;
use App\Util\Lexer\Autolink;
use Illuminate\Support\Str;
use League\Fractal;

class UpdateNote extends Fractal\TransformerAbstract
{
    public function transform(Status $status)
    {
        $mentions = $status->mentions->map(function ($mention) {
            $webfinger = $mention->emailUrl();
            $name = Str::startsWith($webfinger, '@') ?
                $webfinger :
                '@'.$webfinger;

            return [
                'type' => 'Mention',
                'href' => $mention->permalink(),
                'name' => $name,
            ];
        })->toArray();

        if ($status->in_reply_to_id != null) {
            $parent = $status->parent()->profile;
            if ($parent) {
                $webfinger = $parent->emailUrl();
                $name = Str::startsWith($webfinger, '@') ?
                    $webfinger :
                    '@'.$webfinger;
                $reply = [
                    'type' => 'Mention',
                    'href' => $parent->permalink(),
                    'name' => $name,
                ];
                $mentions = array_merge($reply, $mentions);
            }
        }

        $hashtags = $status->hashtags->map(function ($hashtag) {
            return [
                'type' => 'Hashtag',
                'href' => $hashtag->url(),
                'name' => "#{$hashtag->name}",
            ];
        })->toArray();

        $emojis = CustomEmoji::scan($status->caption, true) ?? [];
        $emoji = array_merge($emojis, $mentions);
        $tags = array_merge($emoji, $hashtags);

        $content = $status->caption ? nl2br(Autolink::create()->autolink($status->caption)) : '';
        $latestEdit = $status->edits()->latest()->first();

        return [
            '@context' => [
                'https://w3id.org/security/v1',
                'https://www.w3.org/ns/activitystreams',
                [
                    'Hashtag' => 'as:Hashtag',
                    'sensitive' => 'as:sensitive',
                    'schema' => 'http://schema.org/',
                    'pixelfed' => 'http://pixelfed.org/ns#',
                    'commentsEnabled' => [
                        '@id' => 'pixelfed:commentsEnabled',
                        '@type' => 'schema:Boolean',
                    ],
                    'capabilities' => [
                        '@id' => 'pixelfed:capabilities',
                        '@container' => '@set',
                    ],
                    'announce' => [
                        '@id' => 'pixelfed:canAnnounce',
                        '@type' => '@id',
                    ],
                    'like' => [
                        '@id' => 'pixelfed:canLike',
                        '@type' => '@id',
                    ],
                    'reply' => [
                        '@id' => 'pixelfed:canReply',
                        '@type' => '@id',
                    ],
                    'toot' => 'http://joinmastodon.org/ns#',
                    'Emoji' => 'toot:Emoji',
                ],
            ],
            'id' => $status->permalink('#updates/'.$latestEdit->id),
            'type' => 'Update',
            'actor' => $status->profile->permalink(),
            'published' => $latestEdit->created_at->toAtomString(),
            'to' => $status->scopeToAudience('to'),
            'cc' => $status->scopeToAudience('cc'),
            'object' => [
                'id' => $status->url(),
                'type' => 'Note',
                'summary' => $status->is_nsfw ? $status->cw_summary : null,
                'content' => $content,
                'inReplyTo' => $status->in_reply_to_id ? $status->parent()->url() : null,
                'published' => $status->created_at->toAtomString(),
                'url' => $status->url(),
                'attributedTo' => $status->profile->permalink(),
                'to' => $status->scopeToAudience('to'),
                'cc' => $status->scopeToAudience('cc'),
                'sensitive' => (bool) $status->is_nsfw,
                'attachment' => MediaService::activitypub($status->id, true),
                'tag' => $tags,
                'commentsEnabled' => (bool) ! $status->comments_disabled,
                'updated' => $latestEdit->created_at->toAtomString(),
                'capabilities' => [
                    'announce' => 'https://www.w3.org/ns/activitystreams#Public',
                    'like' => 'https://www.w3.org/ns/activitystreams#Public',
                    'reply' => $status->comments_disabled == true ? '[]' : 'https://www.w3.org/ns/activitystreams#Public',
                ],
                'location' => $status->place_id ? [
                    'type' => 'Place',
                    'name' => $status->place->name,
                    'longitude' => $status->place->long,
                    'latitude' => $status->place->lat,
                    'country' => $status->place->country,
                ] : null,
            ],
        ];
    }
}
