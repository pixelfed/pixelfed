<?php

namespace App\Transformer\Api;

use App\Like;
use App\Status;
use League\Fractal;
use Cache;
use App\Services\HashidService;
use App\Services\LikeService;
use App\Services\MediaService;
use App\Services\MediaTagService;
use App\Services\StatusService;
use App\Services\StatusHashtagService;
use App\Services\StatusLabelService;
use App\Services\StatusMentionService;
use App\Services\ProfileService;
use Illuminate\Support\Str;
use App\Services\PollService;
use App\Models\CustomEmoji;
use App\Services\BookmarkService;
use App\Util\Lexer\Autolink;

class StatusTransformer extends Fractal\TransformerAbstract
{
	public function transform(Status $status)
	{
		$pid = request()->user()->profile_id;
		$taggedPeople = MediaTagService::get($status->id);
		$poll = $status->type === 'poll' ? PollService::get($status->id, $pid) : null;
        $rendered = config('exp.autolink') ?
            ( $status->caption ? Autolink::create()->autolink($status->caption) : '' ) :
            ( $status->rendered ?? $status->caption );

		return [
			'_v'                        => 1,
			'id'                        => (string) $status->id,
			'shortcode'                 => HashidService::encode($status->id),
			'uri'                       => $status->url(),
			'url'                       => $status->url(),
			'in_reply_to_id'            => (string) $status->in_reply_to_id,
			'in_reply_to_account_id'    => (string) $status->in_reply_to_profile_id,
			'reblog'                    => $status->reblog_of_id ? StatusService::get($status->reblog_of_id) : null,
			'content'                   => $rendered,
			'content_text'              => $status->caption,
			'created_at'                => str_replace('+00:00', 'Z', $status->created_at->format(DATE_RFC3339_EXTENDED)),
			'emojis'                    => CustomEmoji::scan($status->caption),
			'reblogs_count'             => 0,
			'favourites_count'          => $status->likes_count ?? 0,
			'reblogged'                 => $status->shared(),
			'favourited'                => $status->liked(),
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
			'account'					=> ProfileService::get($status->profile_id, true),
			'tags'						=> StatusHashtagService::statusTags($status->id),
			'poll'						=> $poll,
			'bookmarked'				=> BookmarkService::get($pid, $status->id),
			'edited_at'					=> $status->edited_at ? str_replace('+00:00', 'Z', $status->edited_at->format(DATE_RFC3339_EXTENDED)) : null,
		];
	}
}
