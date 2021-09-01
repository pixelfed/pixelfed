<?php

namespace App\Util\ActivityPub\Validator;

use Validator;
use Illuminate\Validation\Rule;

class StoryValidator {

	public static function validate($payload)
	{
		$valid = Validator::make($payload, [
			'@context' => 'required',
			'id' => 'required|string',
			'type' => [
				'required',
				Rule::in(['Story'])
			],
			'to' => 'required',
			'attributedTo' => 'required|url',
			'published' => 'required|date',
			'expiresAt' => 'required|date',
			'duration' => 'required|integer|min:1|max:300',
			'can_react' => 'required|boolean',
			'can_reply' => 'required|boolean',
			'attachment' => 'required',
			'attachment.type' => 'required|in:Image,Video',
			'attachment.url' => 'required|url',
			'attachment.mediaType' => 'required'
		])->passes();

		return $valid;
	}
}
