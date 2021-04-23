<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Transformer\Api;

use League\Fractal;
use App\DirectMessage;

class DirectMessageTransformer extends Fractal\TransformerAbstract
{
	public function transform(DirectMessage $dm)
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
