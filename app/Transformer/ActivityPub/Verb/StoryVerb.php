<?php

namespace App\Transformer\ActivityPub\Verb;

use Storage;
use App\Story;
use League\Fractal;
use Illuminate\Support\Str;

class StoryVerb extends Fractal\TransformerAbstract
{
	public function transform(Story $story)
	{
		$type = $story->type == 'photo' ? 'Image' :
			( $story->type == 'video' ? 'Video' :
			'Document' );

		return [
			'@context' => 'https://www.w3.org/ns/activitystreams',
			'id' => $story->url(),
			'type' => 'Story',
			'to' => [
				$story->profile->permalink('/followers')
			],
			'cc' => [],
			'attributedTo' => $story->profile->permalink(),
			'published' => $story->created_at->toAtomString(),
			'expiresAt' => $story->expires_at->toAtomString(),
			'duration' => $story->duration,
			'can_reply' => (bool) $story->can_reply,
			'can_react' => (bool) $story->can_react,
			'attachment' => [
				'type' => $type,
				'url' => url(Storage::url($story->path)),
				'mediaType' => $story->mime,
			],
		];
	}
}
