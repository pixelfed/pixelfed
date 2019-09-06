<?php

namespace App\Transformer\ActivityPub\Verb;

use App\Status;
use League\Fractal;

class CreateNote extends Fractal\TransformerAbstract
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
				[
					'sc'				=> 'http://schema.org#',
					'Hashtag' 			=> 'as:Hashtag',
					'sensitive' 		=> 'as:sensitive',
					'commentsEnabled' 	=> 'sc:Boolean',
					'capabilities'		=> [
						'announce'		=> ['@type' => '@id'],
						'like'			=> ['@type' => '@id'],
						'reply'			=> ['@type' => '@id']
					]
				]
			],
			'id' 					=> $status->permalink(),
			'type' 					=> 'Create',
			'actor' 				=> $status->profile->permalink(),
			'published' 			=> $status->created_at->toAtomString(),
			'to' 					=> $status->scopeToAudience('to'),
			'cc' 					=> $status->scopeToAudience('cc'),
			'object' => [
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
				'commentsEnabled'  => (bool) !$status->comments_disabled,
				'capabilities' => [
					'announce' => 'https://www.w3.org/ns/activitystreams#Public',
					'like' => 'https://www.w3.org/ns/activitystreams#Public',
					'reply' => $status->comments_disabled == true ? null : 'https://www.w3.org/ns/activitystreams#Public'
				],
				'location' => $status->place_id ? [
						'type' => 'Place',
						'name' => $status->place->name,
						'longitude' => $status->place->long,
						'lattitude' => $status->place->lat,
						'country' => $status->place->country
					] : null,
			]
		];
	}
}
