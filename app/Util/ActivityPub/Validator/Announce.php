<?php

namespace App\Util\ActivityPub\Validator;

use Validator;
use Illuminate\Validation\Rule;

class Announce {

	public static function validate($payload)
	{
		$valid = Validator::make($payload, [
			'@context' => 'required',
			'id' => 'required|url',
			'type' => [
				'required',
				Rule::in(['Announce'])
			],
			'actor' => 'required|url',
			'object' => 'required|url'
		])->passes();

		return $valid;
	}
}
