<?php

namespace App\Transformer\Api;

use League\Fractal;

class FilterTransformer extends Fractal\TransformerAbstract
{
	public function transform()
	{
		return [
			'id' => (string) '',
			'phrase' => (string) '',
			'context' => [],
			'expires_at' => null,
			'irreversible' => (bool)false,
			'whole_word' => (bool)false
		];
	}
}
