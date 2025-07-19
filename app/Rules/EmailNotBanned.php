<?php

namespace App\Rules;

use App\Services\EmailService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EmailNotBanned implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (EmailService::isBanned($value)) {
            $fail('Email is invalid.');
        }
    }
}
