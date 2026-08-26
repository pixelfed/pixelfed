<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class WebFinger implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (! is_string($value)) {
            return false;
        }

        $mention = $value;
        if (str_starts_with($mention, '@')) {
            $mention = substr($mention, 1);
        }

        $parts = explode('@', $mention);
        if (count($parts) !== 2) {
            return false;
        }

        [$username, $domain] = $parts;

        if (empty($username) ||
            ! preg_match('/^[a-zA-Z0-9_.-]+$/', $username) ||
            strlen($username) >= 80) {
            return false;
        }

        if (empty($domain) ||
            ! str_contains($domain, '.') ||
            ! preg_match('/^[a-zA-Z0-9.-]+$/', $domain) ||
            strlen($domain) >= 255) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be a valid WebFinger address (username@domain.tld or @username@domain.tld)';
    }
}
