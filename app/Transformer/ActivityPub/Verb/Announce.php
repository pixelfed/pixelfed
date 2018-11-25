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
    		'type' 		=> 'Announce',
    		'actor'		=> $status->profile->permalink(),
    		'object'	=> $status->parent()->url()
    	];
    }
}