<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */
 
namespace App\Transformer\Api\Mastodon\v1;

use App\{
	Notification,
	Status
};
use League\Fractal;

class NotificationTransformer extends Fractal\TransformerAbstract
{
	protected $defaultIncludes = [
		'account',
		'status',
	];

	public function transform(Notification $notification)
	{
		return [
			'id'       		=> (string) $notification->id,
			'type'       	=> $this->replaceTypeVerb($notification->action),
			'created_at' 	=> (string) $notification->created_at->toJSON(),
		];
	}

	public function includeAccount(Notification $notification)
	{
		return $this->item($notification->actor, new AccountTransformer());
	}

	public function includeStatus(Notification $notification)
	{
		$item = $notification;
		if($item->item_id && $item->item_type == 'App\Status') {
			$status = Status::with('media')->find($item->item_id);
			if($status) {
				return $this->item($status, new StatusTransformer());
			} else {
				return;
			}
		} else {
			return;
		}
	}

	public function replaceTypeVerb($verb)
	{
		$verbs = [
			'dm'	=> 'direct',
			'follow' => 'follow',
			'mention' => 'mention',
			'share' => 'reblog',
			'like' => 'favourite',
			'comment' => 'mention',
			'admin.user.modlog.comment' => 'modlog',
			'tagged' => 'tagged'
		];
		return $verbs[$verb];
	}
}
