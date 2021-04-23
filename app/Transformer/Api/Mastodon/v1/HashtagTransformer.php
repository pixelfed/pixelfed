<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

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
