<?php

namespace App\Util\ActivityPub\Validator;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FeatureRequestValidator
{
    public static function validate($payload)
    {
        return Validator::make($payload, [
            '@context' => 'required',
            'id' => 'required|string|url|max:500',
            'type' => [
                'required',
                Rule::in(['FeatureRequest']),
            ],
            'actor' => 'sometimes|nullable|url',
            'object' => 'required',
            'instrument' => 'required',
        ])->passes();
    }
}
