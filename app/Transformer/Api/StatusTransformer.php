<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Transformer\Api;

use App\Status;
use League\Fractal;
use Cache;
use App\Services\HashidService;
use App\Services\MediaTagService;
use App\Services\StatusLabelService;
use Illuminate\Support\Str;

class StatusTransformer extends Fractal\TransformerAbstract
{
    protected $defaultIncludes = [
        'account',
        'media_attachments',
    ];

    public function transform(Status $status)
    {
        $taggedPeople = MediaTagService::get($status->id);
        
        return [
            '_v'                        => 1,
            'id'                        => (string) $status->id,
            'shortcode'                 => HashidService::encode($status->id),
            'uri'                       => $status->url(),
            'url'                       => $status->url(),
            'in_reply_to_id'            => (string) $status->in_reply_to_id,
            'in_reply_to_account_id'    => (string) $status->in_reply_to_profile_id,
            'reblog'                    => null,
            'content'                   => $status->rendered ?? $status->caption,
            'content_text'              => $status->caption,
            'created_at'                => $status->created_at->format('c'),
            'emojis'                    => [],
            'reblogs_count'             => $status->reblogs_count ?? 0,
            'favourites_count'          => $status->likes_count ?? 0,
            'reblogged'                 => $status->shared(),
            'favourited'                => $status->liked(),
            'muted'                     => null,
            'sensitive'                 => (bool) $status->is_nsfw,
            'spoiler_text'              => $status->cw_summary ?? '',
            'visibility'                => $status->visibility ?? $status->scope,
            'application'               => [
                'name'      => 'web',
                'website'   => null
             ],
            'language'                  => null,
            'pinned'                    => null,
            'mentions'                  => [],
            'tags'                      => [],
            'pf_type'                   => $status->type ?? $status->setType(),
            'reply_count'               => (int) $status->reply_count,
            'comments_disabled'         => $status->comments_disabled ? true : false,
            'thread'                    => false,
            'replies'                   => [],
            'parent'                    => [],
            'place'                     => $status->place,
            'local'                     => (bool) $status->local,
            'taggedPeople'              => $taggedPeople,
            'label'                     => StatusLabelService::get($status)
        ];
    }

    public function includeAccount(Status $status)
    {
        $account = $status->profile;

        return $this->item($account, new AccountTransformer());
    }

    public function includeMediaAttachments(Status $status)
    {
        return Cache::remember('status:transformer:media:attachments:'.$status->id, now()->addMinutes(14), function() use($status) {
            if(in_array($status->type, ['photo', 'video', 'video:album', 'photo:album', 'loop', 'photo:video:album'])) {
                $media = $status->media()->orderBy('order')->get();
                return $this->collection($media, new MediaTransformer());
            }
        });
    }
}
