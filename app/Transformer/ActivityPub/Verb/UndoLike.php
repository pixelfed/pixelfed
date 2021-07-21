<?php

namespace App\Transformer\ActivityPub\Verb;

use App\Like as LikeModel;
use League\Fractal;

class UndoLike extends Fractal\TransformerAbstract
{
	public function transform(LikeModel $like)
	{
		return [
			'@context'  => 'https://www.w3.org/ns/activitystreams',
			'id'		=> $like->actor->permalink('#likes/'.$like->id.'/undo'),
			'type' 		=> 'Undo',
			'actor'		=> $like->actor->permalink(),
			'object'	=> [
				'id'		=> $like->actor->permalink('#likes/'.$like->id),
				'type' 		=> 'Like',
				'actor'		=> $like->actor->permalink(),
				'object' 	=> $like->status->url()
			]
		];
	}
}
