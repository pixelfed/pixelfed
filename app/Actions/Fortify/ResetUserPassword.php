<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    /**
     * Validate and reset the user's forgotten password.
     *
     * Mirrors the legacy ResetPasswordController::rules() and
     * validationErrorMessages(): the password must be confirmed, no longer
     * than 72 characters, and satisfy the default password rules. When
     * captcha is enabled, the h-captcha-response field is required as well.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function reset(User $user, array $input): void
    {
        $rules = [
            'password' => ['required', 'confirmed', 'max:72', Password::defaults()],
        ];

        if ((bool) config_cache('captcha.enabled')) {
            $rules['h-captcha-response'] = ['required', 'filled', 'captcha'];
        }

        Validator::make($input, $rules, $this->validationErrorMessages())->validate();

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();
    }

    /**
     * Get the password reset validation error messages.
     *
     * @return array<string, string>
     */
    protected function validationErrorMessages(): array
    {
        return [
            'password.max' => 'Passwords should not exceed 72 characters.',
            'h-captcha-response.required' => 'Failed to validate the captcha.',
            'h-captcha-response.filled' => 'Failed to validate the captcha.',
            'h-captcha-response.captcha' => 'Failed to validate the captcha.',
        ];
    }
}
