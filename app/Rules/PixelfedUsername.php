<?php

namespace App\Rules;

use App\Util\Lexer\RestrictedNames;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PixelfedUsername implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $dash = substr_count($value, '-');
        $underscore = substr_count($value, '_');
        $period = substr_count($value, '.');

        if (ends_with($value, ['.php', '.js', '.css'])) {
            $fail('Username is invalid.');

            return;
        }

        if (($dash + $underscore + $period) > 1) {
            $fail('Username is invalid. Can only contain one dash (-), period (.) or underscore (_).');

            return;
        }

        if (! ctype_alnum($value[0])) {
            $fail('Username is invalid. Must start with a letter or number.');

            return;
        }

        if (! ctype_alnum($value[strlen($value) - 1])) {
            $fail('Username is invalid. Must end with a letter or number.');

            return;
        }

        $val = str_replace(['_', '.', '-'], '', $value);
        if (! ctype_alnum($val)) {
            $fail('Username is invalid. Username must be alpha-numeric and may contain dashes (-), periods (.) and underscores (_).');

            return;
        }

        $restricted = RestrictedNames::get();
        if (in_array(strtolower($value), array_map('strtolower', $restricted))) {
            $fail('Username cannot be used.');

            return;
        }
    }
}
