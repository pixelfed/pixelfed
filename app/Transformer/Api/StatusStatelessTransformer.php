<?php

namespace App\Transformer\Api;

use App\Status;
use League\Fractal;
use Cache;
use App\Services\HashidService;
use App\Services\LikeService;
use App\Services\MediaService;
use App\Services\MediaTagService;
use App\Services\StatusHashtagService;
use App\Services\StatusLabelService;
use App\Services\StatusMentionService;
use App\Services\ProfileService;
use App\Services\PollService;

class StatusStatelessTransformer extends Fractal\TransformerAbstract
{
	public function transform(Status $status)
	{
		$taggedPeople = MediaTagService::get($status->id);
		$poll = $status->type === 'poll' ? PollService::get($status->id) : null;

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
			'reblogs_count'             => 0,
			'favourites_count'          => $status->likes_count ?? 0,
			'reblogged'                 => null,
			'favourited'                => null,
			'muted'                     => null,
			'sensitive'                 => (bool) $status->is_nsfw,
			'spoiler_text'              => $status->cw_summary ?? '',
			'visibility'                => $status->scope ?? $status->visibility,
			'application'               => [
				'name'      => 'web',
				'website'   => null
			 ],
			'language'                  => null,
			'pinned'                    => null,
			'mentions'                  => StatusMentionService::get($status->id),
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
			'label'                     => StatusLabelService::get($status),
			'liked_by'                  => LikeService::likedBy($status),
			'media_attachments'			=> MediaService::get($status->id),
			'account'					=> ProfileService::get($status->profile_id),
			'tags'						=> StatusHashtagService::statusTags($status->id),
			'poll'						=> $poll
		];
	}
}
