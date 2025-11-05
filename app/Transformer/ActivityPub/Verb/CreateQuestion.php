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
}
