<?php

namespace App\Transformer\Api\Mastodon\v1;

use App\Status;
use League\Fractal;
use Cache;

class StatusTransformer extends Fractal\TransformerAbstract
{
    protected $defaultIncludes = [
        'account',
        'media_attachments',
        'mentions',
        'tags',
    ];

    public function transform(Status $status)
    {
        return [
            'id'                        => (string) $status->id,
            'created_at'                => $status->created_at->toJSON(),
            'in_reply_to_id'            => $status->in_reply_to_id ? (string) $status->in_reply_to_id : null,
            'in_reply_to_account_id'    => $status->in_reply_to_profile_id,
            'sensitive'                 => (bool) $status->is_nsfw,
            'spoiler_text'              => $status->cw_summary ?? '',
            'visibility'                => $status->visibility ?? $status->scope,
            'language'                  => 'en',
            'uri'                       => $status->url(),
            'url'                       => $status->url(),
            'replies_count'             => 0,
            'reblogs_count'             => $status->reblogs_count ?? 0,
            'favourites_count'          => $status->likes_count ?? 0,
            'reblogged'                 => $status->shared(),
            'favourited'                => $status->liked(),
            'muted'                     => false,
            'bookmarked'                => false,
            'pinned'                    => false,
            'content'                   => $status->rendered ?? $status->caption ?? '',
            'reblog'                    => null,
            'application'               => [
                'name'      => 'web',
                'website'   => null
             ],
            'mentions'                  => [],
            'tags'                      => [],
            'emojis'                    => [],
            'card'                      => null,
            'poll'                      => null,
        ];
    }

    public function includeAccount(Status $status)
    {
        $account = $status->profile;

        return $this->item($account, new AccountTransformer());
    }

    public function includeMediaAttachments(Status $status)
    {
        return Cache::remember('mastoapi:status:transformer:media:attachments:'.$status->id, now()->addDays(14), function() use($status) {
            if(in_array($status->type, ['photo', 'video', 'photo:album', 'loop', 'photo:video:album'])) {
                $media = $status->media()->orderBy('order')->get();
                return $this->collection($media, new MediaTransformer());
            } else {
                return $this->collection([], new MediaTransformer());
            }
        });
    }

    public function includeMentions(Status $status)
    {
        $mentions = $status->mentions;

        return $this->collection($mentions, new MentionTransformer());
    }

    public function includeTags(Status $status)
    {
        $hashtags = $status->hashtags;

        return $this->collection($hashtags, new HashtagTransformer());
    }
}