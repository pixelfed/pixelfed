<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Transformer\ActivityPub\Verb;

use App\Status;
use League\Fractal;

class DeleteNote extends Fractal\TransformerAbstract
{
	public function transform(Status $status)
	{
		return [
			'@context' => [
				'https://www.w3.org/ns/activitystreams',
				'https://w3id.org/security/v1',
			],
			'id' 					=> $status->permalink('#delete'),
			'type' 					=> 'Delete',
			'actor' 				=> $status->profile->permalink(),
			'object' 				=> [
				'id' 				=> $status->url(),
				'type' 				=> 'Tombstone'
			]
		];
	}

}
