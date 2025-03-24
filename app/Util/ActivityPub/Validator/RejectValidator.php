<?php

namespace App\Util\ActivityPub\Validator;

use Illuminate\Validation\Rule;
use Validator;

class RejectValidator
{
    public static function validate($payload)
    {
        $valid = Validator::make($payload, [
            '@context' => 'required',
            'id' => 'required|string',
            'type' => [
                'required',
                Rule::in(['Reject']),
            ],
            'actor' => 'required|url',
            'object.id' => 'required|url',
            'object.actor' => 'required|url',
            'object.object' => 'required|url',
            'object.type' => [
                'required',
                Rule::in(['Follow']),
            ],
        ])->passes();

        return $valid;
    }
}
