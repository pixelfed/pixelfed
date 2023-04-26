<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use App\Services\BouncerService;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

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
     * Display the form to request a password reset link.
     *
     * @return \Illuminate\View\View
     */
    public function showLinkRequestForm()
    {
		if(config('pixelfed.bouncer.cloud_ips.ban_logins')) {
			abort_if(BouncerService::checkIp(request()->ip()), 404);
		}

		usleep(random_int(100000, 300000));

        return view('auth.passwords.email');
    }

    /**
     * Validate the email for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function validateEmail(Request $request)
    {
		if(config('pixelfed.bouncer.cloud_ips.ban_logins')) {
			abort_if(BouncerService::checkIp($request->ip()), 404);
		}

		usleep(random_int(100000, 3000000));

    	if(config('captcha.enabled')) {
            $rules = [
	    		'email' => 'required|email',
            	'h-captcha-response' => 'required|captcha'
            ];
        } else {
	    	$rules = [
	    		'email' => 'required|email'
	    	];
        }

        $request->validate($rules, [
        	'h-captcha-response' => 'Failed to validate the captcha.',
        ]);
    }

    /**
     * Get the response for a failed password reset link.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function sendResetLinkFailedResponse(Request $request, $response)
    {
        if ($request->wantsJson()) {
            throw ValidationException::withMessages([
                'email' => [trans($response)],
            ]);
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors([
            	'email' => trans($response),
            ]);
    }
}
