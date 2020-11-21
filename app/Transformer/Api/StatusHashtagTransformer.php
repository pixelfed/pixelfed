<?php

namespace App\Transformer\Api;

use App\{Hashtag, Status, StatusHashtag};
use League\Fractal;

class StatusHashtagTransformer extends Fractal\TransformerAbstract
{
	public function transform(StatusHashtag $statusHashtag)
	{
		$hashtag = $statusHashtag->hashtag;
		$status = $statusHashtag->status;
		$profile = $statusHashtag->profile;
		
		return [
			'status' => [
				'id'			=> (int) $status->id,
				'type' 			=> $status->type,
				'url' 			=> $status->url(),
				'thumb' 		=> $status->thumb(true),
				'filter' 		=> $status->firstMedia()->filter_class,
				'sensitive' 	=> (bool) $status->is_nsfw,
				'like_count' 	=> $status->likes_count,
				'share_count' 	=> $status->reblogs_count,
				'user' => [
					'username' 	=> $profile->username,
					'url'		=> $profile->url(),
				],
				'visibility' 	=> $status->visibility ?? $status->scope
			],
			'hashtag' => [
				'name' 			=> $hashtag->name,
				'url'  			=> $hashtag->url(),
			]
		];
	}
}
