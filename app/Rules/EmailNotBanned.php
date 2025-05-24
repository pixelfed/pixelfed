<?php

namespace App\Rules;

use Closure;
use App\Services\EmailService;
use Illuminate\Contracts\Validation\ValidationRule;

class EmailNotBanned implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (EmailService::isBanned($value)) {
            $fail('Email is invalid.');
        }
    }
}
