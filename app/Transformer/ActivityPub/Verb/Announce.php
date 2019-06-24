<?php

namespace App\Transformer\ActivityPub\Verb;

use App\Status;
use League\Fractal;

class Announce extends Fractal\TransformerAbstract
{
    public function transform(Status $status)
    {
    	return [
    		'@context'  => 'https://www.w3.org/ns/activitystreams',
    		'id'		=> $status->permalink(),
    		'type' 		=> 'Announce',
    		'actor'		=> $status->profile->permalink(),
    		'to' 		=> ['https://www.w3.org/ns/activitystreams#Public'],
    		'cc' 		=> [
    			$status->parent()->profile->permalink(),
    			$status->parent()->profile->follower_url
    		],
    		'published' => $status->created_at->format(DATE_ISO8601),
    		'object'	=> $status->parent()->url(),
    	];
    }
}