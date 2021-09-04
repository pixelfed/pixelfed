<?php

namespace App\Transformer\ActivityPub\Verb;

use Storage;
use App\Story;
use League\Fractal;
use Illuminate\Support\Str;

class CreateStory extends Fractal\TransformerAbstract
{
	public function transform(Story $story)
	{
		return [
			'@context' => 'https://www.w3.org/ns/activitystreams',
			'id' => $story->permalink(),
			'type' => 'Add',
			'actor' => $story->profile->permalink(),
			'to' => [
				$story->profile->permalink('/followers')
			],
			'object' => [
				'id' => $story->url(),
				'type' => 'Story',
				'object' => $story->bearcapUrl(),
			]
		];
	}
}
