<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Rules\ValidUsername;
use App\Services\BouncerService;
use App\Services\EmailService;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Purify;
use Spatie\Honeypot\Exceptions\SpamException;
use Spatie\Honeypot\SpamProtection;

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
     * Honeypot spam protection is applied here rather than as route middleware:
     * Fortify self-registers the register route, so middleware cannot be
     * attached inline, and attaching it to the named route at boot time does
     * not persist to the dispatched route stack in this project. Calling
     * SpamProtection::check() against the current request scopes the protection
     * to registration only (login/password-reset are untouched) and honors
     * config('honeypot.enabled'), which is disabled in the test environment.
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     * @throws HttpResponseException
     * @throws SpamException
     */
    public function create(array $input): User
    {
        $this->ensureRegistrationIsAvailable();

        app(SpamProtection::class)->check(request()->all());

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

        $rules = [
            'agecheck' => 'required|accepted',
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
}
