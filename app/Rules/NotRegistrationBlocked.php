<?php

namespace App\Rules;

use App\Services\RegistrationBlockService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use InvalidArgumentException;

class NotRegistrationBlocked implements ValidationRule
{
    public function __construct(private readonly string $field)
    {
        if (! in_array($field, ['email', 'username'], true)) {
            throw new InvalidArgumentException('NotRegistrationBlocked supports email or username.');
        }
    }

    /**
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if ($this->field === 'email' && RegistrationBlockService::emailBlocked($value)) {
            $fail(RegistrationBlockService::EMAIL_MESSAGE);

            return;
        }

        if ($this->field === 'username' && RegistrationBlockService::usernameBlocked($value)) {
            $fail(RegistrationBlockService::USERNAME_MESSAGE);
        }
    }
}
