<?php

namespace App\Util\ActivityPub\Validator;

use Validator;
use Illuminate\Validation\Rule;

class Announce
{

    public static function validate($payload)
    {
        $valid = Validator::make($payload, [
            '@context' => 'required',
            'id' => 'required|string',
            'type' => [
                'required',
                Rule::in(['Announce'])
            ],
            'actor' => 'required|url|active_url',
            'published' => 'required|date',
            'to'    => 'required',
            'cc'    => 'required',
            'object' => 'required|url|active_url'
        ])->passes();

        return $valid;
    }
}
