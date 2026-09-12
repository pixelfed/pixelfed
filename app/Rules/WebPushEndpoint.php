<?php

namespace App\Rules;

use App\Services\WebPushEndpointGuard;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects a Web Push endpoint the server should not be making requests to.
 *
 * This is the registration-time half of the check; WebPushNotifyPipeline
 * runs the same inspection again at send time and pins the connection to the
 * addresses it validated, because DNS can change in between. See
 * WebPushEndpointGuard.
 */
class WebPushEndpoint implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('The :attribute must be a valid push endpoint.');

            return;
        }

        $result = WebPushEndpointGuard::inspect($value);

        if (! $result['ok']) {
            $fail('The :attribute '.$result['reason'].'.');
        }
    }
}
