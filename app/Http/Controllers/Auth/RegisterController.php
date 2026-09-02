<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\ValidUsername;
use App\Services\BouncerService;
use App\Services\EmailService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Purify;

#[Middleware('guest')]
class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/i/web';

    public function getRegisterToken()
    {
        return Cache::remember('pf:register:rt', 900, function () {
            return Str::random(40);
        });
    }

    /**
     * Get a validator for an incoming registration request.
     *
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validator(array $data)
    {
        if (config('database.default') == 'pgsql') {
            $data['username'] = strtolower($data['username']);
            $data['email'] = strtolower($data['email']);
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
                $banned = EmailService::isBanned($value);
                if ($banned) {
                    return $fail('Email is invalid.');
                }
            },
        ];

        $rt = [
            'required',
            function ($attribute, $value, $fail) {
                if ($value !== $this->getRegisterToken()) {
                    return $fail('Something went wrong');
                }
            },
        ];

        $rules = [
            'agecheck' => 'required|accepted',
            'rt' => $rt,
            'name' => 'nullable|string|max:'.config('pixelfed.max_name_length'),
            'username' => $usernameRules,
            'email' => $emailRules,
            'password' => 'required|string|min:'.config('pixelfed.min_password_length').'|confirmed',
        ];

        if ((bool) config_cache('captcha.enabled') && (bool) config_cache('captcha.active.register')) {
            $rules['h-captcha-response'] = 'required|captcha';
        }

        return Validator::make($data, $rules);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     *
     * @return User
     */
    public function create(array $data)
    {
        if (config('database.default') == 'pgsql') {
            $data['username'] = strtolower($data['username']);
            $data['email'] = strtolower($data['email']);
        }

        return User::create([
            'name' => Purify::clean($data['name']),
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'app_register_ip' => request()->ip(),
        ]);
    }

    /**
     * Show the application registration form.
     *
     * @return Response
     */
    public function showRegistrationForm(): RedirectResponse|View
    {
        if ((bool) config_cache('pixelfed.open_registration')) {
            if (config('pixelfed.bouncer.cloud_ips.ban_signups')) {
                abort_if(BouncerService::checkIp(request()->ip()), 404);
            }
            $hasLimit = config('pixelfed.enforce_max_users');
            if ($hasLimit) {
                $limit = config('pixelfed.max_users');
                $count = User::where(function ($q) {
                    return $q->whereNull('status')->orWhereNotIn('status', ['deleted', 'delete']);
                })->count();
                if ($limit <= $count) {
                    return redirect(route('help.instance-max-users-limit'));
                }
                abort_if($limit <= $count, 404);

                return view('auth.register');
            } else {
                return view('auth.register');
            }
        } else {
            if ((bool) config_cache('instance.curated_registration.enabled') && config('instance.curated_registration.state.fallback_on_closed_reg')) {
                return redirect('/auth/sign_up');
            } else {
                abort(404);
            }
        }
    }

    /**
     * Handle a registration request for the application.
     *
     * @return Response
     */
    public function register(Request $request)
    {
        abort_if(config_cache('pixelfed.open_registration') == false, 400);

        if (config('pixelfed.bouncer.cloud_ips.ban_signups')) {
            abort_if(BouncerService::checkIp($request->ip()), 404);
        }

        $hasLimit = config('pixelfed.enforce_max_users');
        if ($hasLimit) {
            $count = User::where(function ($q) {
                return $q->whereNull('status')->orWhereNotIn('status', ['deleted', 'delete']);
            })->count();
            $limit = config('pixelfed.max_users');

            if ($limit && $limit <= $count) {
                return redirect(route('help.instance-max-users-limit'));
            }
        }

        $this->validator($request->all())->validate();

        event(new Registered($user = $this->create($request->all())));

        $this->guard()->login($user);

        return $this->registered($request, $user)
            ?: redirect($this->redirectPath());
    }
}
