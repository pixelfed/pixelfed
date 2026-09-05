<?php

namespace App\Http\Responses;

use App\Models\AccountLog;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    /**
     * The path users are redirected to after a successful login.
     */
    protected string $redirectTo = '/i/web';

    /**
     * Create an HTTP response that represents the object.
     *
     * Mirrors the legacy LoginController::authenticated() audit log write and
     * the '/i/web' redirect destination.
     *
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        $user = $request->user();

        if ($user && $user->status !== 'deleted') {
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

        if ($request->wantsJson()) {
            return response()->json([
                'redirect' => $this->redirectTo,
                'two_factor' => false,
            ]);
        }

        return redirect()->intended($this->redirectTo);
    }
}
