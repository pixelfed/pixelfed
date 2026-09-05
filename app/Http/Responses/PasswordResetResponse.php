<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\PasswordResetResponse as PasswordResetResponseContract;
use Symfony\Component\HttpFoundation\Response;

class PasswordResetResponse implements PasswordResetResponseContract
{
    /**
     * The path a user is redirected to after a successful password reset.
     *
     * Mirrors the legacy ResetPasswordController redirectTo of '/i/web'.
     */
    protected string $redirectTo = '/i/web';

    /**
     * Create an HTTP response that represents the object.
     *
     * On a successful password reset, HTML callers are redirected to '/i/web'
     * while JSON callers receive a minimal success payload indicating the same
     * redirect destination.
     */
    public function toResponse($request): Response
    {
        /** @var Request $request */
        if ($request->wantsJson()) {
            return new JsonResponse(['redirect' => $this->redirectTo], 200);
        }

        return redirect()->intended($this->redirectTo);
    }
}
