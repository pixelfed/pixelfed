<?php

namespace App\Transformer\Api;

use League\Fractal;

class FilterTransformer extends Fractal\TransformerAbstract
{
	public function transform()
	{
		return [
			'id' => '',
			'phrase' => '',
			'context' => [],
			'expires_at' => null,
			'irreversible' => false,
			'whole_word' => false
		];
	}
}
