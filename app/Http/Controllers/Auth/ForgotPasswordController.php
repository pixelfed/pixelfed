<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\BouncerService;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

#[Middleware('guest')]
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
     * Display the form to request a password reset link.
     */
    public function showLinkRequestForm(): View
    {
        if (config('pixelfed.bouncer.cloud_ips.ban_logins')) {
            abort_if(BouncerService::checkIp(request()->ip()), 404);
        }

        usleep(random_int(100000, 300000));

        return view('auth.passwords.email');
    }

    /**
     * Validate the email for the given request.
     */
    public function validateEmail(Request $request): void
    {
        if (config('pixelfed.bouncer.cloud_ips.ban_logins')) {
            abort_if(BouncerService::checkIp($request->ip()), 404);
        }

        usleep(random_int(100000, 3000000));

        if ((bool) config_cache('captcha.enabled')) {
            $rules = [
                'email' => 'required|email',
                'h-captcha-response' => 'required|captcha',
            ];
        } else {
            $rules = [
                'email' => 'required|email',
            ];
        }

        $request->validate($rules, [
            'h-captcha-response' => 'Failed to validate the captcha.',
        ]);
    }

    /**
     * Get the response for a failed password reset link.
     *
     * @param  string  $response
     *
     * @throws ValidationException
     */
    public function sendResetLinkFailedResponse(Request $request, $response): RedirectResponse
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
