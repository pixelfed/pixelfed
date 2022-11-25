<?php

namespace GCS\OIDCClient\Controllers;


use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\RedirectsUsers;


class OIDCController extends Controller
{

    use RedirectsUsers,ValidatesRequests;
    use AuthorizesRequests, DispatchesJobs;


    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    public function signin()
    {
        $this->guard()->redirect();
    }

    public function callback(Request $request)
    {
        $userInfo = $this->guard()->retrieveUserInfo();
        $user = $this->guard()->generateUser($userInfo);
        
        if ($this->guard()->login($user)) {
            return $this->sendLoginResponse($request);
        }
        return $this->sendFailedLoginResponse($request);
    }

    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();
        
        return redirect()->intended($this->redirectPath());
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            'user' => [trans('auth.failed')],
        ]);
    }

    public function signout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        return redirect()->intended($this->redirectPath());
    }
    
    private function guard()
    {
        return Auth::guard();
    }
    
}
