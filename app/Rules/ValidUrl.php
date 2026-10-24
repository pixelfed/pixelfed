<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidUrl implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // A non-string (array/object) can arrive from a malformed or
        // spec-compliant federated payload where a url field is an array;
        // fail validation instead of letting strtolower() throw a TypeError.
        if (! is_string($value) || ! str_starts_with(strtolower($value), 'https://')) {
            $fail('The :attribute must start with https://.');
        }
    }
}
