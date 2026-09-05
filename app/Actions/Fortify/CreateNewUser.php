<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Rules\ValidUsername;
use App\Services\BouncerService;
use App\Services\EmailService;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Purify;

class CreateNewUser implements CreatesNewUsers
{
    /**
     * Validate and create a newly registered user.
     *
     * Reproduces the legacy RegisterController::validator() + create() behavior.
     * Orchestrates the availability guard, validation, and persistence so the
     * public registration path is unchanged.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     * @throws HttpResponseException
     */
    public function create(array $input): User
    {
        $this->ensureRegistrationIsAvailable();

        $this->validator($input)->validate();

        return $this->persist($input);
    }

    /**
     * Build the registration validator.
     *
     * Mirrors the legacy RegisterController::validator() shape so callers can
     * invoke ->validate() on the returned instance. Applies pgsql lowercasing to
     * a working copy of the input, matching the legacy behavior.
     *
     * @param  array<string, mixed>  $input
     */
    public function validator(array $input): ValidatorContract
    {
        if (config('database.default') === 'pgsql') {
            $input['username'] = strtolower($input['username']);
            $input['email'] = strtolower($input['email']);
        }

        $usernameRules = [
            'required',
            'min:2',
            'max:30',
            'unique:users',
            new ValidUsername,
        ];

        $emailRules = [
            'required',
            'string',
            'email:rfc,dns,spoof',
            'max:255',
            'unique:users',
            function ($attribute, $value, $fail) {
                if (EmailService::isBanned($value)) {
                    return $fail('Email is invalid.');
                }
            },
        ];

        $registerTokenRules = [
            'required',
            function ($attribute, $value, $fail) {
                if ($value !== self::getRegisterToken()) {
                    return $fail('Something went wrong');
                }
            },
        ];

        $rules = [
            'agecheck' => 'required|accepted',
            'rt' => $registerTokenRules,
            'name' => 'nullable|string|max:'.config('pixelfed.max_name_length'),
            'username' => $usernameRules,
            'email' => $emailRules,
            'password' => 'required|string|min:'.config('pixelfed.min_password_length').'|confirmed',
        ];

        if ((bool) config_cache('captcha.enabled') && (bool) config_cache('captcha.active.register')) {
            $rules['h-captcha-response'] = 'required|captcha';
        }

        return Validator::make($input, $rules);
    }

    /**
     * Persist a new user from validated registration input.
     *
     * Applies pgsql lowercasing then creates the user, matching the legacy
     * RegisterController::create() behavior (Purify name, bcrypt password,
     * app_register_ip).
     *
     * @param  array<string, mixed>  $input
     */
    public function persist(array $input): User
    {
        if (config('database.default') === 'pgsql') {
            $input['username'] = strtolower($input['username']);
            $input['email'] = strtolower($input['email']);
        }

        return User::create([
            'name' => Purify::clean($input['name']),
            'username' => $input['username'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'app_register_ip' => request()->ip(),
        ]);
    }

    /**
     * Enforce registration availability before any user is created.
     *
     * Reproduces the legacy RegisterController::register() pre-checks:
     * aborts 400 when registration is closed, aborts 404 on a banned signup IP,
     * and redirects to the max-users help page when the instance is full.
     *
     * @throws HttpResponseException
     */
    private function ensureRegistrationIsAvailable(): void
    {
        abort_if(config_cache('pixelfed.open_registration') == false, 400);

        if (config('pixelfed.bouncer.cloud_ips.ban_signups')) {
            abort_if(BouncerService::checkIp(request()->ip()), 404);
        }

        if (config('pixelfed.enforce_max_users')) {
            $count = User::where(function ($query) {
                return $query->whereNull('status')->orWhereNotIn('status', ['deleted', 'delete']);
            })->count();
            $limit = config('pixelfed.max_users');

            if ($limit && $limit <= $count) {
                throw new HttpResponseException(redirect(route('help.instance-max-users-limit')));
            }
        }
    }

    /**
     * Get (or refresh) the cached registration token.
     *
     * Single non-controller source of truth for the register token, replacing the
     * legacy RegisterController::getRegisterToken(). Referenced by the registration
     * views and ParentalControlsController.
     */
    public static function getRegisterToken(): string
    {
        return Cache::remember('pf:register:rt', 900, function () {
            return Str::random(40);
        });
    }
}
