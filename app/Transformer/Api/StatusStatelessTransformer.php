<?php

namespace App\Transformer\Api;

use App\Status;
use League\Fractal;
use Cache;
use App\Services\AccountService;
use App\Services\HashidService;
use App\Services\LikeService;
use App\Services\MediaService;
use App\Services\MediaTagService;
use App\Services\StatusService;
use App\Services\StatusHashtagService;
use App\Services\StatusLabelService;
use App\Services\StatusMentionService;
use App\Services\PollService;
use App\Models\CustomEmoji;

class StatusStatelessTransformer extends Fractal\TransformerAbstract
{
	public function transform(Status $status)
	{
		$taggedPeople = MediaTagService::get($status->id);
		$poll = $status->type === 'poll' ? PollService::get($status->id) : null;

		return [
			'_v'                        => 1,
			'id'                        => (string) $status->id,
			//'gid'						=> $status->group_id ? (string) $status->group_id : null,
			'shortcode'                 => HashidService::encode($status->id),
			'uri'                       => $status->url(),
			'url'                       => $status->url(),
			'in_reply_to_id'            => $status->in_reply_to_id ? (string) $status->in_reply_to_id : null,
			'in_reply_to_account_id'    => $status->in_reply_to_profile_id ? (string) $status->in_reply_to_profile_id : null,
			'reblog'                    => $status->reblog_of_id ? StatusService::get($status->reblog_of_id, false) : null,
			'content'                   => $status->rendered ?? $status->caption,
			'content_text'              => $status->caption,
			'created_at'                => str_replace('+00:00', 'Z', $status->created_at->format(DATE_RFC3339_EXTENDED)),
			'emojis'                    => CustomEmoji::scan($status->caption),
			'reblogs_count'             => $status->reblogs_count ?? 0,
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
			'mentions'                  => StatusMentionService::get($status->id),
			'pf_type'                   => $status->type ?? $status->setType(),
			'reply_count'               => (int) $status->reply_count,
			'comments_disabled'         => (bool) $status->comments_disabled,
			'thread'                    => false,
			'replies'                   => [],
			'parent'                    => [],
			'place'                     => $status->place,
			'local'                     => (bool) $status->local,
			'taggedPeople'              => $taggedPeople,
			'label'                     => StatusLabelService::get($status),
			'liked_by'                  => LikeService::likedBy($status),
			'media_attachments'			=> MediaService::get($status->id),
			'account'					=> AccountService::get($status->profile_id, true),
			'tags'						=> StatusHashtagService::statusTags($status->id),
			'poll'						=> $poll,
			'edited_at'					=> $status->edited_at ? str_replace('+00:00', 'Z', $status->edited_at->format(DATE_RFC3339_EXTENDED)) : null,
		];
	}
}
