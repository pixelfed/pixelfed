<?php

namespace App\Transformer\ActivityPub\Verb;

use App\Status;
use League\Fractal;
use App\Models\CustomEmoji;
use Illuminate\Support\Str;

class Note extends Fractal\TransformerAbstract
{
	public function transform(Status $status)
	{

		$mentions = $status->mentions->map(function ($mention) {
			$webfinger = $mention->emailUrl();
			$name = Str::startsWith($webfinger, '@') ? 
				$webfinger :
				'@' . $webfinger;
			return [
				'type' => 'Mention',
				'href' => $mention->permalink(),
				'name' => $name
			];
		})->toArray();

		if($status->in_reply_to_id != null) {
			$parent = $status->parent()->profile;
			if($parent) {
				$webfinger = $parent->emailUrl();
				$name = Str::startsWith($webfinger, '@') ? 
					$webfinger :
					'@' . $webfinger;
				$reply = [
					'type' => 'Mention',
					'href' => $parent->permalink(),
					'name' => $name
				];
				array_push($mentions, $reply);
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

		return [
			'@context' => [
				'https://w3id.org/security/v1',
				'https://www.w3.org/ns/activitystreams',
				[
					'Hashtag' 			=> 'as:Hashtag',
					'sensitive' 		=> 'as:sensitive',
					'schema' 			=> 'http://schema.org/',
					'pixelfed' 			=> 'http://pixelfed.org/ns#',
					'commentsEnabled' 	=> [
						'@id' 			=> 'pixelfed:commentsEnabled',
						'@type' 		=> 'schema:Boolean'
					],
					'capabilities'		=> [
						'@id' 			=> 'pixelfed:capabilities',
						'@container' 	=> '@set'
					],
					'announce'			=> [
						'@id' 			=> 'pixelfed:canAnnounce',
						'@type' 		=> '@id'
					],
					'like'				=> [
						'@id' 			=> 'pixelfed:canLike',
						'@type' 		=> '@id'
					],
					'reply'				=> [
						'@id' 			=> 'pixelfed:canReply',
						'@type' 		=> '@id'
					],
					'toot' 				=> 'http://joinmastodon.org/ns#',
					'Emoji'				=> 'toot:Emoji',
					'blurhash'			=> 'toot:blurhash',
				]
			],
			'id' 				=> $status->url(),
			'type' 				=> 'Note',
			'summary'   		=> $status->is_nsfw ? $status->cw_summary : null,
			'content'   		=> $status->rendered ?? $status->caption,
			'inReplyTo' 		=> $status->in_reply_to_id ? $status->parent()->url() : null,
			'published'    		=> $status->created_at->toAtomString(),
			'url'          		=> $status->url(),
			'attributedTo' 		=> $status->profile->permalink(),
			'to'           		=> $status->scopeToAudience('to'),
			'cc' 				=> $status->scopeToAudience('cc'),
			'sensitive'       	=> (bool) $status->is_nsfw,
			'attachment'      	=> $status->media()->orderBy('order')->get()->map(function ($media) {
				$res = [
					'type'      => $media->activityVerb(),
					'mediaType' => $media->mime,
					'url'       => $media->url(),
					'name'      => $media->caption,
				];
				if($media->blurhash) {
					$res['blurhash'] = $media->blurhash;
				}
				if($media->width) {
					$res['width'] = $media->width;
				}
				if($media->height) {
					$res['height'] = $media->height;
				}
				return $res;
			})->toArray(),
			'tag' 				=> $tags,
			'commentsEnabled'  => (bool) !$status->comments_disabled,
			'capabilities' => [
				'announce' => 'https://www.w3.org/ns/activitystreams#Public',
				'like' => 'https://www.w3.org/ns/activitystreams#Public',
				'reply' => $status->comments_disabled == true ? '[]' : 'https://www.w3.org/ns/activitystreams#Public'
			],
			'location' => $status->place_id ? [
					'type' => 'Place',
					'name' => $status->place->name,
					'longitude' => $status->place->long,
					'latitude' => $status->place->lat,
					'country' => $status->place->country
				] : null,
		];
	}
}
