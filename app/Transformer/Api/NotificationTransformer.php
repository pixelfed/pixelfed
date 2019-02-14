<?php

namespace App\Transformer\Api;

use App\Notification;
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
			'created_at' 	=> (string) $notification->created_at,
			'account' 		=> null,
			'status' 		=> null
		];
	}

	public function includeAccount(Notification $notification)
	{
		return $this->item($notification->actor, new AccountTransformer());
	}

	public function includeStatus(Notification $notification)
	{
		$item = $notification->item;
		if(is_object($item) && get_class($item) === 'App\Status') {
			return $this->item($item, new StatusTransformer());
		} else {
			return null;
		}
	}

	public function replaceTypeVerb($verb)
	{
		$verbs = [
			'follow' => 'follow',
			'mention' => 'mention',
			'reblog' => 'share',
			'share' => 'share',
			'like' => 'favourite',
			'comment' => 'comment',
		];
		return $verbs[$verb];
	}
}
