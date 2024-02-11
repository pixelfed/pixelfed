<?php

namespace App\Util\ActivityPub\Validator;

use Illuminate\Validation\Rule;
use Validator;

class Like
{
    public static function validate($payload)
    {
        $valid = Validator::make($payload, [
            '@context' => 'required',
            'id' => 'required|string',
            'type' => [
                'required',
                Rule::in(['Like']),
            ],
            'actor' => 'required|url',
            'object' => 'required|url',
        ])->passes();

        return $valid;
    }
}
