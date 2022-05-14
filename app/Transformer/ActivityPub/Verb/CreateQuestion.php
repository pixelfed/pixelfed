<?php

namespace App\Transformer\ActivityPub\Verb;

use App\Status;
use League\Fractal;
use Illuminate\Support\Str;

class CreateQuestion extends Fractal\TransformerAbstract
{
	protected $defaultIncludes = [
        'object',
    ];

	public function transform(Status $status)
	{
		return [
			'@context' => [
				'https://www.w3.org/ns/activitystreams',
				'https://w3id.org/security/v1',
				[
					'sc'				=> 'http://schema.org#',
					'Hashtag' 			=> 'as:Hashtag',
					'sensitive' 		=> 'as:sensitive',
					'commentsEnabled' 	=> 'sc:Boolean',
					'@capabilities'		=> [
						'@announce'			=> '@id',
						'@like'				=> '@id',
						'@reply'			=> '@id',
					],
				]
			],
			'id' 					=> $status->permalink(),
			'type' 					=> 'Create',
			'actor' 				=> $status->profile->permalink(),
			'published' 			=> $status->created_at->toAtomString(),
			'to' 					=> $status->scopeToAudience('to'),
			'cc' 					=> $status->scopeToAudience('cc'),
		];
	}

	public function includeObject(Status $status)
	{
		return $this->item($status, new Question());
	}
}
