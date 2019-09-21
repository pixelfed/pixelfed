<?php

namespace App\Transformer\Api;

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
		'relationship'
	];

	public function transform(Notification $notification)
	{
		return [
			'id'       		=> (string) $notification->id,
			'type'       	=> $this->replaceTypeVerb($notification->action),
			'created_at' 	=> (string) $notification->created_at->toISOString(),
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
				return null;
			}
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

	public function includeRelationship(Notification $notification)
	{
		return $this->item($notification->actor, new RelationshipTransformer());
	}
}
