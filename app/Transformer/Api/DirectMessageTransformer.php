<?php

namespace App\Transformer\Api;

use League\Fractal;
use App\DirectMessage;

class DirectMessageTransformer extends Fractal\TransformerAbstract
{
	public function transform(DirectMessage $dm): array
	{
		return [
			'id' 					=> $dm->id,
			'to_id' 				=> $dm->to_id,
			'from_id' 				=> $dm->from_id,
			'from_profile_ids' 		=> $dm->from_profile_ids,
			'group_message' 		=> $dm->group_message,
			'status_id' 			=> $dm->status_id,
			'read_at' 				=> $dm->read_at,
			'created_at' 			=> $dm->created_at
		];
	}
}
