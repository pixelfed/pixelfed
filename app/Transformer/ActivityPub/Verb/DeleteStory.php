<?php

namespace App\Transformer\ActivityPub\Verb;

use Storage;
use App\Story;
use League\Fractal;
use Illuminate\Support\Str;

class DeleteStory extends Fractal\TransformerAbstract
{
	public function transform(Story $story)
	{
		return [
			'@context' => 'https://www.w3.org/ns/activitystreams',
			'id' => $story->url() . '#delete',
			'type' => 'Delete',
			'actor' => $story->profile->permalink(),
			'object' => [
				'id' => $story->url(),
				'type' => 'Story',
			],
		];
	}
}
