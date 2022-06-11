<?php

namespace App\Transformer\ActivityPub\Verb;

use App\FollowRequest;
use League\Fractal;

class RejectFollow extends Fractal\TransformerAbstract
{
	public function transform(FollowRequest $follow)
	{
		return [
			'@context'  => 'https://www.w3.org/ns/activitystreams',
			'type'      => 'Reject',
			'id'		=> $follow->permalink(null, '#rejects'),
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
