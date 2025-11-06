<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ExpoPushTokenRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value) {
            $fail('The :attribute must not be empty.');
        }

        if (str_starts_with($value, 'ExponentPushToken[') && mb_strlen($value) < 26) {
            $fail('The :attribute is not a valid push token.');
        }

        if (! str_starts_with($value, 'ExponentPushToken[') && ! str_starts_with($value, 'ExpoPushToken[')) {
            $fail('The :attribute is not a valid push token.');
        }

        if (! str_ends_with($value, ']')) {
            $fail('The :attribute is not a valid push token.');
        }
    }
}
