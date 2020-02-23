<?php

namespace App\Transformer\Api;

use League\Fractal;

class ContextTransformer extends Fractal\TransformerAbstract
{
	public function transform(): array
	{
		return [
			'ancestors' => [],
			'descendants' => []
		];
	}
}
