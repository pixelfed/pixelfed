<?php

namespace App\Transformer\Api;

use League\Fractal;

class ApplicationTransformer extends Fractal\TransformerAbstract
{
	public function transform()
	{
		return [
			'name'    => '',
			'website' => null,
		];
	}
}
