<?php

namespace App\Util\ActivityPub\Validator;

use Validator;
use Illuminate\Validation\Rule;

class Follow {

	public static function validate($payload)
	{
		$valid = Validator::make($payload, [
			'@context' => 'required',
			'id' => 'required|string',
			'type' => [
				'required',
				Rule::in(['Follow'])
			],
			'actor' => 'required|url',
			'object' => 'required|url'
		])->passes();

		return $valid;
	}
}