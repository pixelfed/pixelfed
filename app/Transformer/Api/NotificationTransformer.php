<?php

namespace App\Transformer\Api;

use App\Notification;
use App\Status;
use League\Fractal;

class NotificationTransformer extends Fractal\TransformerAbstract
{
	protected $defaultIncludes = [
		'account',
		'status',
		'relationship',
		'modlog'
	];

	public function transform(Notification $notification)
	{
		return [
			'id'       		=> (string) $notification->id,
			'type'       	=> $this->replaceTypeVerb($notification->action),
			'created_at' 	=> (string) $notification->created_at->format('c'),
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
			'admin.user.modlog.comment' => 'modlog'
		];
		return $verbs[$verb];
	}

	public function includeRelationship(Notification $notification)
	{
		return $this->item($notification->actor, new RelationshipTransformer());
	}

	public function includeModlog(Notification $notification)
	{
		$n = $notification;
		if($n->item_id && $n->item_type == 'App\ModLog') {
			$ml = $n->item;
			if(!empty($ml)) {
				$res = $this->item($ml, function($ml) {
					return [
						'id' => $ml->object_uid,
						'url' => url('/i/admin/users/modlogs/' . $ml->object_uid)
					];
				});
				return $res;
			} else {
				return null;
			}
		} else {
			return null;
		}
	}
}
