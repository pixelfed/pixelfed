<?php

namespace App\Util\ActivityPub\Validator;

use Illuminate\Validation\Rule;
use Validator;

class MoveValidator
{
    public static function validate($payload)
    {
        return Validator::make($payload, [
            '@context' => 'required',
            'type' => [
                'required',
                Rule::in(['Move']),
            ],
            'actor' => 'required|url',
            'object' => 'required|url',
            'target' => 'required|url',
        ])->passes();
    }
}
