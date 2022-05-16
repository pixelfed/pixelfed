<?php

namespace App\Transformer\ActivityPub\Verb;

use App\Status;
use League\Fractal;
use Illuminate\Support\Str;

class Question extends Fractal\TransformerAbstract
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
						'@capabilities'		=> [
							'@announce'			=> '@id',
							'@like'				=> '@id',
							'@reply'			=> '@id',
						],
					]
				],
				'id' 				=> $status->url(),
				'type' 				=> 'Question',
				'summary'   		=> null,
				'content'   		=> $status->rendered ?? $status->caption,
				'inReplyTo' 		=> $status->in_reply_to_id ? $status->parent()->url() : null,
				'published'    		=> $status->created_at->toAtomString(),
				'url'          		=> $status->url(),
				'attributedTo' 		=> $status->profile->permalink(),
				'to'           		=> $status->scopeToAudience('to'),
				'cc' 				=> $status->scopeToAudience('cc'),
				'sensitive'       	=> (bool) $status->is_nsfw,
				'attachment'      	=> [],
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
						'latitude' => $status->place->lat,
						'country' => $status->place->country
					] : null,
				'endTime' => $status->poll->expires_at->toAtomString(),
				'oneOf' => collect($status->poll->poll_options)->map(function($option, $index) use($status) {
					return [
						'type' => 'Note',
						'name' => $option,
						'replies' => [
							'type' => 'Collection',
							'totalItems' => $status->poll->cached_tallies[$index]
						]
					];
				})
			];
	}
}
