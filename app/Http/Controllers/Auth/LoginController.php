<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AccountLog;
use App\Models\User;
use App\Services\BouncerService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

#[Middleware('guest', except: ['logout'])]
class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/i/web';

    protected $maxAttempts = 5;

    protected $decayMinutes = 60;

    public function showLoginForm(): View
    {
        if (config('pixelfed.bouncer.cloud_ips.ban_logins')) {
            abort_if(BouncerService::checkIp(request()->ip()), 404);
        }

        return view('auth.login');
    }

    /**
     * Validate the user login request.
     *
     * @param  Request  $request
     */
    public function validateLogin($request): void
    {
        if (config('pixelfed.bouncer.cloud_ips.ban_logins')) {
            abort_if(BouncerService::checkIp($request->ip()), 404);
        }

        $rules = [
            $this->username() => 'required|email',
            'password' => 'required|string|min:6',
        ];
        $messages = [];

        if (
            (bool) config_cache('captcha.enabled') &&
            (bool) config_cache('captcha.active.login') ||
            (
                (bool) config_cache('captcha.triggers.login.enabled') &&
                request()->session()->has('login_attempts') &&
                request()->session()->get('login_attempts') >= config('captcha.triggers.login.attempts')
            )
        ) {
            $rules['h-captcha-response'] = 'required|filled|captcha|min:5';
            $messages['h-captcha-response.required'] = 'The captcha must be filled';
        }
        $request->validate($rules, $messages);
    }

    /**
     * The user has been authenticated.
     *
     * @param  Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated($request, $user): void
    {
        if ($user->status == 'deleted') {
            return;
        }

        $log = new AccountLog;
        $log->user_id = $user->id;
        $log->item_id = $user->id;
        $log->item_type = User::class;
        $log->action = 'auth.login';
        $log->message = 'Account Login';
        $log->link = null;
        $log->ip_address = $request->ip();
        $log->user_agent = $request->userAgent();
        $log->save();
    }

    /**
     * Get the failed login response instance.
     *
     * @return Response
     *
     * @throws ValidationException
     */
    protected function sendFailedLoginResponse(Request $request): void
    {
        if (config('captcha.triggers.login.enabled')) {
            if ($request->session()->has('login_attempts')) {
                $ct = $request->session()->get('login_attempts');
                $request->session()->put('login_attempts', $ct + 1);
            } else {
                $request->session()->put('login_attempts', 1);
            }
        }

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }
}
