<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Transformer\ActivityPub\Verb;

use App\Like as LikeModel;
use League\Fractal;

class Like extends Fractal\TransformerAbstract
{
	public function transform(LikeModel $like)
	{
		return [
			'@context'  => 'https://www.w3.org/ns/activitystreams',
			'id'		=> $like->actor->permalink('#likes/'.$like->id),
			'type' 		=> 'Like',
			'actor'		=> $like->actor->permalink(),
			'object'	=> $like->status->url()
		];
	}
}
