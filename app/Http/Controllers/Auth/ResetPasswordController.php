<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\Request;
use App\Services\BouncerService;
use Illuminate\Validation\Rules;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
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

    /**
     * Get the password reset validation rules.
     *
     * @return array
     */
    protected function rules()
    {
    	usleep(random_int(100000, 3000000));

        if(config('captcha.enabled')) {
            return [
	            'token' => 'required',
	            'email' => 'required|email',
	            'password' => ['required', 'confirmed', 'max:72', Rules\Password::defaults()],
            	'h-captcha-response' => ['required' ,'filled', 'captcha']
	       	];
        }

        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', 'max:72', Rules\Password::defaults()],
        ];
    }

    /**
     * Get the password reset validation error messages.
     *
     * @return array
     */
    protected function validationErrorMessages()
    {
        return [
        	'password.max' => 'Passwords should not exceed 72 characters.',
        	'h-captcha-response.required' => 'Failed to validate the captcha.',
        	'h-captcha-response.filled' => 'Failed to validate the captcha.',
        	'h-captcha-response.captcha' => 'Failed to validate the captcha.',
        ];
    }

    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showResetForm(Request $request)
    {
		if(config('pixelfed.bouncer.cloud_ips.ban_logins')) {
			abort_if(BouncerService::checkIp($request->ip()), 404);
		}

		usleep(random_int(100000, 300000));

        $token = $request->route()->parameter('token');

        return view('auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    public function reset(Request $request)
    {
		if(config('pixelfed.bouncer.cloud_ips.ban_logins')) {
			abort_if(BouncerService::checkIp($request->ip()), 404);
		}

        $request->validate($this->rules(), $this->validationErrorMessages());

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $response = $this->broker()->reset(
            $this->credentials($request), function ($user, $password) {
                $this->resetPassword($user, $password);
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $response == Password::PASSWORD_RESET
                    ? $this->sendResetResponse($request, $response)
                    : $this->sendResetFailedResponse($request, $response);
    }

    /**
     * Get the password reset credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        return $request->only(
            'email', 'password', 'password_confirmation', 'token'
        );
    }

    /**
     * Get the response for a failed password reset.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetFailedResponse(Request $request, $response)
    {
        if ($request->wantsJson()) {
            throw ValidationException::withMessages(['email' => [trans($response)]]);
        }
        return redirect()->back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => [trans($response)]]);
    }

}
