<?php

namespace App\Transformer\Api;

use App\Notification;
use App\Services\AccountService;
use App\Services\HashidService;
use App\Services\RelationshipService;
use App\Services\StatusService;
use League\Fractal;

class NotificationTransformer extends Fractal\TransformerAbstract
{
	public function transform(Notification $notification)
	{
		$res = [
			'id'       		=> (string) $notification->id,
			'type'       	=> $this->replaceTypeVerb($notification->action),
			'created_at' 	=> (string) $notification->created_at->format('c'),
		];

		$n = $notification;

		if($n->actor_id) {
			$res['account'] = AccountService::get($n->actor_id);
			$res['relationship'] = RelationshipService::get($n->actor_id, $n->profile_id);
		}

		if($n->item_id && $n->item_type == 'App\Status') {
			$res['status'] = StatusService::get($n->item_id, false);
		}

		if($n->item_id && $n->item_type == 'App\ModLog') {
			$ml = $n->item;
			$res['modlog'] = [
				'id' => $ml->object_uid,
				'url' => url('/i/admin/users/modlogs/' . $ml->object_uid)
			];
		}

		if($n->item_id && $n->item_type == 'App\MediaTag') {
			$ml = $n->item;
			$res['tagged'] = [
				'username' => $ml->tagged_username,
				'post_url' => '/p/'.HashidService::encode($ml->status_id)
			];
		}

		return $res;
	}

	public function replaceTypeVerb($verb)
	{
		$verbs = [
			'dm'	=> 'direct',
			'follow' => 'follow',
			'mention' => 'mention',
			'reblog' => 'share',
			'share' => 'share',
			'like' => 'favourite',
			'group:like' => 'favourite',
			'comment' => 'comment',
			'admin.user.modlog.comment' => 'modlog',
			'tagged' => 'tagged',
			'group:comment' => 'group:comment',
			'story:react' => 'story:react',
			'story:comment' => 'story:comment',
			'group:join:approved' => 'group:join:approved',
			'group:join:rejected' => 'group:join:rejected'
		];

		if(!isset($verbs[$verb])) {
			return $verb;
		}

		return $verbs[$verb];
	}
}
