<?php

namespace App\Util\ActivityPub\Validator;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Accept
{
    public static function validate($payload)
    {
        if (! is_array($payload)) {
            return false;
        }

        $rules = [
            '@context' => 'required',
            'id' => 'required|string',
            'type' => [
                'required',
                Rule::in(['Accept']),
            ],
            'actor' => 'required|url',
        ];

        $object = $payload['object'] ?? null;

        if (is_string($object)) {
            $rules['object'] = 'required|url';
        } elseif (is_array($object) && ! isset($object['actor']) && ! isset($object['object'])) {
            $rules['object'] = 'required|array';
            $rules['object.id'] = 'required|url';
            $rules['object.type'] = [
                'required',
                Rule::in(['Follow']),
            ];
        } else {
            $rules['object'] = 'required';
            $rules['object.id'] = 'required|url';
            $rules['object.type'] = [
                'required',
                Rule::in(['Follow']),
            ];
            $rules['object.actor'] = 'required|url';
            $rules['object.object'] = 'required|url|same:actor';
        }

        return Validator::make($payload, $rules)->passes();
    }
}
