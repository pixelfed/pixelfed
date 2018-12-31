<?php

namespace App\Transformer\Api;

use League\Fractal;

class ResultsTransformer extends Fractal\TransformerAbstract
{

	protected $defaultIncludes = [
		'account',
		'mentions',
		'media_attachments',
		'tags',
	];
	public function transform()
	{
		return [
			'accounts' => [],
			'statuses' => [],
			'hashtags' => []
		];
	}
}
