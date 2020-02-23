<?php

namespace App\Transformer\Api;

use App\Hashtag;
use League\Fractal;

class HashtagTransformer extends Fractal\TransformerAbstract
{
	public function transform(Hashtag $hashtag): array
	{
		return [
			'name' => $hashtag->name,
			'url'  => $hashtag->url(),
		];
	}
}
