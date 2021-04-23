<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Transformer\ActivityPub\Verb;

use App\Status;
use League\Fractal;

class Announce extends Fractal\TransformerAbstract
{
	public function transform(Status $status)
	{
		return [
			'@context'  => 'https://www.w3.org/ns/activitystreams',
			'id'		=> $status->permalink(),
			'type' 		=> 'Announce',
			'actor'		=> $status->profile->permalink(),
			'to' 		=> ['https://www.w3.org/ns/activitystreams#Public'],
			'cc' 		=> [
				$status->profile->permalink(),
				$status->profile->follower_url ?? $status->profile->permalink('/followers')
			],
			'published' => $status->created_at->format(DATE_ISO8601),
			'object'	=> $status->parent()->url(),
		];
	}
}
