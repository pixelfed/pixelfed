<?php

namespace App\Transformer\Api\Mastodon\v1;

use App\Status;
use League\Fractal;
use Cache;
use App\Services\MediaService;
use App\Services\ProfileService;
use App\Services\StatusHashtagService;

class StatusTransformer extends Fractal\TransformerAbstract
{
	public function transform(Status $status)
	{
		return [
			'id'                        => (string) $status->id,
			'created_at'                => $status->created_at->toJSON(),
			'in_reply_to_id'            => $status->in_reply_to_id ? (string) $status->in_reply_to_id : null,
			'in_reply_to_account_id'    => $status->in_reply_to_profile_id ? (string) $status->in_reply_to_profile_id : null,
			'sensitive'                 => (bool) $status->is_nsfw,
			'spoiler_text'              => $status->cw_summary ?? '',
			'visibility'                => $status->visibility ?? $status->scope,
			'language'                  => 'en',
			'uri'                       => $status->permalink(''),
			'url'                       => $status->url(),
			'replies_count'             => $status->reply_count ?? 0,
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
			'media_attachments'         => MediaService::get($status->id),
			'account'                   => ProfileService::get($status->profile_id),
			'tags'                      => StatusHashtagService::statusTags($status->id),
		];
	}
}
