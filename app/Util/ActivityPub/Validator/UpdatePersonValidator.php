<?php

namespace App\Util\ActivityPub\Validator;

use Closure;
use Illuminate\Validation\Rule;
use Validator;

class UpdatePersonValidator
{
    public static function validate($payload)
    {
        $valid = Validator::make($payload, [
            '@context' => 'required',
            'id' => 'required|string|url',
            'type' => [
                'required',
                Rule::in(['Update']),
            ],
            'actor' => 'required|url',
            'object' => 'required',
            'object.id' => [
                'required',
                'url',
                'same:actor',
                function (string $attribute, mixed $value, Closure $fail) use ($payload) {
                    self::sameHost($attribute, $value, $fail, $payload['actor']);
                },
            ],
            'object.type' => [
                'required',
                Rule::in(['Person']),
            ],
            'object.publicKey' => 'required',
            'object.publicKey.id' => [
                'required',
                'url',
                function (string $attribute, mixed $value, Closure $fail) use ($payload) {
                    self::sameHost($attribute, $value, $fail, $payload['actor']);
                },
            ],
            'object.publicKey.owner' => [
                'required',
                'url',
                'same:actor',
                function (string $attribute, mixed $value, Closure $fail) use ($payload) {
                    self::sameHost($attribute, $value, $fail, $payload['actor']);
                },
            ],
            'object.publicKey.publicKeyPem' => 'required|string',
            'object.url' => [
                'required',
                'url',
                function (string $attribute, mixed $value, Closure $fail) use ($payload) {
                    self::sameHost($attribute, $value, $fail, $payload['actor']);
                },
            ],
            'object.summary' => 'required|string|nullable',
            'object.preferredUsername' => 'required|string',
            'object.name' => 'required|string|nullable',
            'object.inbox' => [
                'required',
                'url',
                function (string $attribute, mixed $value, Closure $fail) use ($payload) {
                    self::sameHost($attribute, $value, $fail, $payload['actor']);
                },
            ],
            'object.outbox' => [
                'required',
                'url',
                function (string $attribute, mixed $value, Closure $fail) use ($payload) {
                    self::sameHost($attribute, $value, $fail, $payload['actor']);
                },
            ],
            'object.following' => [
                'required',
                'url',
                function (string $attribute, mixed $value, Closure $fail) use ($payload) {
                    self::sameHost($attribute, $value, $fail, $payload['actor']);
                },
            ],
            'object.followers' => [
                'required',
                'url',
                function (string $attribute, mixed $value, Closure $fail) use ($payload) {
                    self::sameHost($attribute, $value, $fail, $payload['actor']);
                },
            ],
            'object.manuallyApprovesFollowers' => 'required',
            'object.icon' => 'sometimes|nullable',
            'object.icon.type' => 'sometimes|required_with:object.icon.url,object.icon.mediaType|in:Image',
            'object.icon.url' => 'sometimes|required_with:object.icon.type,object.icon.mediaType|url',
            'object.icon.mediaType' => 'sometimes|required_with:object.icon.url,object.icon.type|in:image/jpeg,image/png,image/jpg',
            'object.endpoints' => 'sometimes',
            'object.endpoints.sharedInbox' => [
                'sometimes',
                'url',
                function (string $attribute, mixed $value, Closure $fail) use ($payload) {
                    self::sameHost($attribute, $value, $fail, $payload['actor']);
                },
            ],
        ])->passes();

        return $valid;
    }

    public static function sameHost(string $attribute, mixed $value, Closure $fail, string $comparedHost)
    {
        if (empty($value)) {
            $fail('The '.$attribute.' is invalid or empty');
        }
        $host = parse_url($value, PHP_URL_HOST);
        $idHost = parse_url($comparedHost, PHP_URL_HOST);
        if ($host !== $idHost) {
            $fail('The '.$attribute.' is invalid');
        }
    }
}
