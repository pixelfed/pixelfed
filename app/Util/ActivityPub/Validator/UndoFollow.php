<?php

namespace App\Util\ActivityPub\Validator;

use Validator;
use Illuminate\Validation\Rule;

class UndoFollow
{

    public static function validate($payload)
    {
        $valid = Validator::make($payload, [
            '@context' => 'required',
            'id' => 'required|string',
            'type' => [
                'required',
                Rule::in(['Undo'])
            ],
            'actor' => 'required|url',
            'object.actor' => 'required|url',
            'object.object' => 'required|url',
            'object.type' => [
                'required',
                Rule::in(['Follow'])
            ],
        ])->passes();

        return $valid;
    }
}
