<?php

namespace App\Transformer\Api;

use App\Services\AccountService;
use App\Services\Groups\GroupMediaService;
use League\Fractal;

class GroupPostTransformer extends Fractal\TransformerAbstract
{
    public function transform($status)
    {
        return [
            'id' => (string) $status->id,
            'gid' => $status->group_id ? (string) $status->group_id : null,
            'url' => '/groups/'.$status->group_id.'/p/'.$status->id,
            'content' => $status->caption,
            'content_text' => $status->caption,
            'created_at' => str_replace('+00:00', 'Z', $status->created_at->format(DATE_RFC3339_EXTENDED)),
            'reblogs_count' => $status->reblogs_count ?? 0,
            'favourites_count' => $status->likes_count ?? 0,
            'reblogged' => null,
            'favourited' => null,
            'muted' => null,
            'sensitive' => (bool) $status->is_nsfw,
            'spoiler_text' => $status->cw_summary ?? '',
            'visibility' => $status->visibility,
            'application' => [
                'name' => 'web',
                'website' => null,
            ],
            'language' => null,
            'pf_type' => $status->type,
            'reply_count' => (int) $status->reply_count ?? 0,
            'comments_disabled' => (bool) $status->comments_disabled,
            'thread' => false,
            'media_attachments' => GroupMediaService::get($status->id),
            'replies' => [],
            'parent' => [],
            'place' => null,
            'local' => (bool) ! $status->remote_url,
            'account' => AccountService::get($status->profile_id, true),
            'poll' => [],
        ];
    }
}
