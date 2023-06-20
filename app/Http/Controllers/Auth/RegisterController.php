<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Purify;
use App\Util\Lexer\RestrictedNames;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use App\Services\EmailService;
use App\Services\BouncerService;

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

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->middleware('guest');
	}

	public function getRegisterToken()
	{
		return \Cache::remember('pf:register:rt', 900, function() {
			return str_random(40);
		});
	}

	/**
	 * Get a validator for an incoming registration request.
	 *
	 * @param array $data
	 *
	 * @return \Illuminate\Contracts\Validation\Validator
	 */
	protected function validator(array $data)
	{
		if(config('database.default') == 'pgsql') {
			$data['username'] = strtolower($data['username']);
			$data['email'] = strtolower($data['email']);
		}

		$usernameRules = [
			'required',
			'min:2',
			'max:15',
			'unique:users',
			function ($attribute, $value, $fail) {
				$dash = substr_count($value, '-');
				$underscore = substr_count($value, '_');
				$period = substr_count($value, '.');

				if(ends_with($value, ['.php', '.js', '.css'])) {
					return $fail('Username is invalid.');
				}

				if(($dash + $underscore + $period) > 1) {
					return $fail('Username is invalid. Can only contain one dash (-), period (.) or underscore (_).');
				}

				if (!ctype_alnum($value[0])) {
					return $fail('Username is invalid. Must start with a letter or number.');
				}

				if (!ctype_alnum($value[strlen($value) - 1])) {
					return $fail('Username is invalid. Must end with a letter or number.');
				}

				$val = str_replace(['_', '.', '-'], '', $value);
				if(!ctype_alnum($val)) {
					return $fail('Username is invalid. Username must be alpha-numeric and may contain dashes (-), periods (.) and underscores (_).');
				}

				$restricted = RestrictedNames::get();
				if (in_array(strtolower($value), array_map('strtolower', $restricted))) {
					return $fail('Username cannot be used.');
				}
			},
		];

		$emailRules = [
			'required',
			'string',
			'email',
			'max:255',
			'unique:users',
			function ($attribute, $value, $fail) {
				$banned = EmailService::isBanned($value);
				if($banned) {
					return $fail('Email is invalid.');
				}
			},
		];

		$rt = [
			'required',
			function ($attribute, $value, $fail) {
				if($value !== $this->getRegisterToken()) {
					return $fail('Something went wrong');
				}
			}
		];

		$rules = [
			'agecheck' => 'required|accepted',
			'rt' 	   => $rt,
			'name'     => 'nullable|string|max:'.config('pixelfed.max_name_length'),
			'username' => $usernameRules,
			'email'    => $emailRules,
			'password' => 'required|string|min:'.config('pixelfed.min_password_length').'|confirmed',
		];

		if(config('captcha.enabled') || config('captcha.active.register')) {
			$rules['h-captcha-response'] = 'required|captcha';
		}

		return Validator::make($data, $rules);
	}

	/**
	 * Create a new user instance after a valid registration.
	 *
	 * @param array $data
	 *
	 * @return \App\User
	 */
	protected function create(array $data)
	{
		if(config('database.default') == 'pgsql') {
			$data['username'] = strtolower($data['username']);
			$data['email'] = strtolower($data['email']);
		}

		return User::create([
			'name'     => Purify::clean($data['name']),
			'username' => $data['username'],
			'email'    => $data['email'],
			'password' => Hash::make($data['password']),
			'app_register_ip' => request()->ip()
		]);
	}

	/**
	 * Show the application registration form.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function showRegistrationForm()
	{
		if(config_cache('pixelfed.open_registration')) {
			if(config('pixelfed.bouncer.cloud_ips.ban_signups')) {
				abort_if(BouncerService::checkIp(request()->ip()), 404);
			}
			$hasLimit = config('pixelfed.enforce_max_users');
			if($hasLimit) {
				$limit = config('pixelfed.max_users');
				$count = User::where(function($q){ return $q->whereNull('status')->orWhereNotIn('status', ['deleted','delete']); })->count();
				if($limit <= $count) {
					return redirect(route('help.instance-max-users-limit'));
				}
				abort_if($limit <= $count, 404);
				return view('auth.register');
			} else {
				return view('auth.register');
			}
		} else {
			abort(404);
		}
	}

	/**
	 * Handle a registration request for the application.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function register(Request $request)
	{
		abort_if(config_cache('pixelfed.open_registration') == false, 400);

		if(config('pixelfed.bouncer.cloud_ips.ban_signups')) {
			abort_if(BouncerService::checkIp($request->ip()), 404);
		}

		$hasLimit = config('pixelfed.enforce_max_users');
		if($hasLimit) {
			$count = User::where(function($q){ return $q->whereNull('status')->orWhereNotIn('status', ['deleted','delete']); })->count();
			$limit = config('pixelfed.max_users');

    		if($limit && $limit <= $count) {
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
