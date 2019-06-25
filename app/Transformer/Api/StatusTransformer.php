<?php

namespace App\Transformer\Api;

use App\Status;
use League\Fractal;
use Cache;

class StatusTransformer extends Fractal\TransformerAbstract
{
    protected $defaultIncludes = [
        'account',
        'mentions',
        'media_attachments',
        'tags',
    ];

    public function transform(Status $status)
    {
        return [
            'id'                        => (string) $status->id,
            'uri'                       => $status->url(),
            'url'                       => $status->url(),
            'in_reply_to_id'            => $status->in_reply_to_id,
            'in_reply_to_account_id'    => $status->in_reply_to_profile_id,
            'reblog'                    => null,
            'content'                   => $status->rendered ?? $status->caption,
            'created_at'                => $status->created_at->format('c'),
            'emojis'                    => [],
            'reblogs_count'             => $status->reblogs_count != 0 ? $status->reblogs_count: $status->shares()->count(),
            'favourites_count'          => $status->likes_count != 0 ? $status->likes_count: $status->likes()->count(),
            'reblogged'                 => $status->shared(),
            'favourited'                => $status->liked(),
            'muted'                     => null,
            'sensitive'                 => (bool) $status->is_nsfw,
            'spoiler_text'              => $status->cw_summary,
            'visibility'                => $status->visibility ?? $status->scope,
            'application'               => [
                'name'      => 'web',
                'website'   => null
             ],
            'language'                  => null,
            'pinned'                    => null,

            'pf_type'                   => $status->type ?? $status->setType(),
            'reply_count'               => (int) $status->reply_count,
            'comments_disabled'         => $status->comments_disabled ? true : false,
            'thread'                    => false,
            'replies'                   => [],
            'parent'                    => [],
        ];
    }

    public function includeAccount(Status $status)
    {
        $account = $status->profile;

        return $this->item($account, new AccountTransformer());
    }

    public function includeMentions(Status $status)
    {
        $mentions = $status->mentions;

        return $this->collection($mentions, new MentionTransformer());
    }

    public function includeMediaAttachments(Status $status)
    {
        return Cache::remember('status:transformer:media:attachments:'.$status->id, now()->addDays(14), function() use($status) {
            if(in_array($status->type, ['photo', 'video', 'photo:album', 'loop', 'photo:video:album'])) {
                $media = $status->media()->orderBy('order')->get();
                return $this->collection($media, new MediaTransformer());
            }
        });
    }

    public function includeTags(Status $status)
    {
        $tags = $status->hashtags;

        return $this->collection($tags, new HashtagTransformer());
    }
}
