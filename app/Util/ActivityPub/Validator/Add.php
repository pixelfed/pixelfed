<?php

namespace App\Util\ActivityPub\Validator;

use Validator;
use Illuminate\Validation\Rule;

class Add {

	public static function validate($payload)
	{
		$valid = Validator::make($payload, [
			'@context' => 'required',
			'id' => 'required|string',
			'type' => [
				'required',
				Rule::in(['Add'])
			],
			'actor' => 'required|url',
			'object' => 'required',
			'object.id' => 'required|url',
			'object.type' => [
				'required',
				Rule::in(['Story'])
			],
			'object.attributedTo' => 'required|url|same:actor',
			'object.attachment' => 'required',
			'object.attachment.type' => [
				'required',
				Rule::in(['Image'])
			],
			'object.attachment.url' => 'required|url',
			'object.attachment.mediaType' => [
				'required',
				Rule::in(['image/jpeg', 'image/png'])
			]
		])->passes();

		return $valid;
	}
}