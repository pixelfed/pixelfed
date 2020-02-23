<?php

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

	public function transform(Notification $notification): array
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
				return null;
			}
		} else {
			return null;
		}
	}

	public function replaceTypeVerb($verb): string
	{
		$verbs = [
			'follow' => 'follow',
			'mention' => 'mention',
			'share' => 'reblog',
			'like' => 'favourite',
			'comment' => 'mention',
		];
		return $verbs[$verb];
	}
}
