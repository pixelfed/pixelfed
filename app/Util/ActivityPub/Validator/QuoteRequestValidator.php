<?php

namespace App\Util\ActivityPub\Validator;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class QuoteRequestValidator
{
    public static function validate($payload)
    {
        return Validator::make($payload, [
            '@context' => 'required',
            'id' => 'required|string|url|max:500',
            'type' => [
                'required',
                Rule::in(['QuoteRequest']),
            ],
            'actor' => 'required',
            'object' => 'required',
            'instrument' => 'required',
        ])->passes();
    }
}
