<?php

namespace App\Transformer\ActivityPub\Verb;

use App\Follower;
use League\Fractal;

class Follow extends Fractal\TransformerAbstract
{
    public function transform($follower)
    {
    	return [
    		'@context'  => 'https://www.w3.org/ns/activitystreams',
    		'type' 		=> 'Follow',
    		'actor'		=> $follower->actor->permalink(),
    		'object'	=> $follower->target->permalink()
    	];
    }
}