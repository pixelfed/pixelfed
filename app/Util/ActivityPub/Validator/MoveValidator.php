<?php

namespace App\Util\ActivityPub\Validator;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
