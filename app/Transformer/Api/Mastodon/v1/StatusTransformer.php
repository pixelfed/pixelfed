<?php

namespace App\Transformer\Api\Mastodon\v1;

use App\Services\MediaService;
use App\Services\ProfileService;
use App\Services\StatusHashtagService;
use App\Status;
use App\Util\Lexer\Autolink;
use League\Fractal;

class StatusTransformer extends Fractal\TransformerAbstract
{
    public function transform(Status $status)
    {
        $content = $status->caption ? nl2br(Autolink::create()->autolink($status->caption)) : '';

        return [
            'id' => (string) $status->id,
            'created_at' => $status->created_at->toJSON(),
            'in_reply_to_id' => $status->in_reply_to_id ? (string) $status->in_reply_to_id : null,
            'in_reply_to_account_id' => $status->in_reply_to_profile_id ? (string) $status->in_reply_to_profile_id : null,
            'sensitive' => (bool) $status->is_nsfw,
            'spoiler_text' => $status->cw_summary ?? '',
            'visibility' => $status->visibility ?? $status->scope,
            'language' => 'en',
            'uri' => $status->permalink(''),
            'url' => $status->url(),
            'replies_count' => $status->reply_count ?? 0,
            'reblogs_count' => $status->reblogs_count ?? 0,
            'favourites_count' => $status->likes_count ?? 0,
            'reblogged' => $status->shared(),
            'favourited' => $status->liked(),
            'muted' => false,
            'bookmarked' => false,
            'content' => $content,
            'reblog' => null,
            'application' => [
                'name' => 'web',
                'website' => null,
            ],
            'mentions' => [],
            'emojis' => [],
            'card' => null,
            'poll' => null,
            'media_attachments' => MediaService::get($status->id),
            'account' => ProfileService::get($status->profile_id, true),
            'tags' => StatusHashtagService::statusTags($status->id),
        ];
    }
}
