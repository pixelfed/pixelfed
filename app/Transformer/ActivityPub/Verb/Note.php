<?php

namespace App\Transformer\ActivityPub\Verb;

use App\Status;
use League\Fractal;

class Note extends Fractal\TransformerAbstract
{
	public function transform(Status $status)
	{

		$mentions = $status->mentions->map(function ($mention) {
			return [
				'type' => 'Mention',
				'href' => $mention->permalink(),
				'name' => $mention->emailUrl()
			];
		})->toArray();
		$hashtags = $status->hashtags->map(function ($hashtag) {
			return [
				'type' => 'Hashtag',
				'href' => $hashtag->url(),
				'name' => "#{$hashtag->name}",
			];
		})->toArray();
		$tags = array_merge($mentions, $hashtags);

		return [
			'@context' => [
				'https://www.w3.org/ns/activitystreams',
				'https://w3id.org/security/v1',
			],
			'id' 				=> $status->url(),
			'type' 				=> 'Note',
			'summary'   		=> null,
			'content'   		=> $status->rendered ?? $status->caption,
			'inReplyTo' 		=> $status->in_reply_to_id ? $status->parent()->url() : null,
			'published'    		=> $status->created_at->toAtomString(),
			'url'          		=> $status->url(),
			'attributedTo' 		=> $status->profile->permalink(),
			'to'           		=> $status->scopeToAudience('to'),
			'cc' 				=> $status->scopeToAudience('cc'),
			'sensitive'       	=> (bool) $status->is_nsfw,
			'attachment'      	=> $status->media()->orderBy('order')->get()->map(function ($media) {
				return [
					'type'      => $media->activityVerb(),
					'mediaType' => $media->mime,
					'url'       => $media->url(),
					'name'      => $media->caption,
				];
			})->toArray(),
			'tag' 				=> $tags,
		];
	}
}
