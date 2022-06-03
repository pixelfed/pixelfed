<?php

namespace App\Transformer\ActivityPub\Verb;

use App\FollowRequest;
use League\Fractal;

class AcceptFollow extends Fractal\TransformerAbstract
{
	public function transform(FollowRequest $follow)
	{
		return [
			'@context'  => 'https://www.w3.org/ns/activitystreams',
			'type'      => 'Accept',
			'id'		=> $follow->permalink(),
			'actor'     => $follow->target->permalink(),
			'object' 	=> [
				'type' 		=> 'Follow',
				'id'        => $follow->activity && isset($follow->activity['id']) ? $follow->activity['id'] : null,
				'actor'		=> $follow->actor->permalink(),
				'object'	=> $follow->target->permalink()
			]
		];
	}
}
