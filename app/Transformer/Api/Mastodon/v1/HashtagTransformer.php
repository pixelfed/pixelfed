<?php

namespace App\Transformer\Api\Mastodon\v1;

use App\Hashtag;
use League\Fractal;

class HashtagTransformer extends Fractal\TransformerAbstract
{
	public function transform(Hashtag $hashtag)
	{
		return [
			'name' => $hashtag->name,
			'url'  => $hashtag->url(),
		];
	}
}
