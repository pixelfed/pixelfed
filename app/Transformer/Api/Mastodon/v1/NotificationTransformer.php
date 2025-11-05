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
