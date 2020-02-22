<?php

namespace App\Util\ActivityPub\Validator;

use Validator;
use Illuminate\Validation\Rule;

class Accept
{

    public static function validate($payload)
    {
        $valid = Validator::make($payload, [
            '@context' => 'required',
            'id' => 'required|string',
            'type' => [
                'required',
                Rule::in(['Accept'])
            ],
            'actor' => 'required|url',
            'object' => 'required',
            'object.id' => 'required|url',
            'object.type' => [
                'required',
                Rule::in(['Follow'])
            ],
            'object.actor' => 'required|url',
            'object.object' => 'required|url|same:actor',
        ])->passes();

        return $valid;
    }
}
