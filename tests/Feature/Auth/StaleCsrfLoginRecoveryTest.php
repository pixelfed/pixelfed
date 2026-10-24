<?php

use App\Models\User;
use App\Services\PendingLoginService;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Session\Session;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpFoundation\Response;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Stale CSRF login recovery
|--------------------------------------------------------------------------
|
| The multi-step login flow (credentials -> 2fa / email verify) regenerates
| the session on each step transition, which invalidates the CSRF token held
| by other tabs / back-button forms. A stale-token POST to a login endpoint
| must be recovered into a friendly redirect back to the correct step instead
| of the stock Symfony 419 "Page Expired" page.
|
| The framework's exception handler rewrites TokenMismatchException into an
| HttpException(419) in prepareException() BEFORE user render callbacks are
| dispatched, so the recovery callback must be keyed on the rewritten type and
| identify the CSRF case via status code + previous exception. These tests
| drive the handler directly because PreventRequestForgery bypasses CSRF while
| running unit tests, so a kernel-level POST can never raise a real mismatch.
|
*/

/**
 * Render an exception through the application's real exception handler for a
 * given request, exercising prepareException() + renderViaCallbacks().
 */
function renderThroughHandler(Request $request, Throwable $e): Response
{
    app()->instance('request', $request);

    return app(ExceptionHandler::class)->render($request, $e);
}

/**
 * Build a browser POST request to a login endpoint that carries the same kind
 * of stale token a second tab would submit. Returns the request together with
 * its Laravel session store so callers can seed pending-login state.
 *
 * @return array{0: Request, 1: Session}
 */
function stalePost(string $uri): array
{
    $session = app('session.store');
    $request = Request::create($uri, 'POST', ['_token' => 'stale-token']);
    $request->headers->set('Accept', 'text/html');
    $request->setLaravelSession($session);

    return [$request, $session];
}

it('redirects a stale-CSRF browser POST /login to the login form with a message', function () {
    [$request] = stalePost('/login');

    $response = renderThroughHandler($request, new TokenMismatchException('CSRF token mismatch.'));

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getStatusCode())->toBe(302)
        ->and($response->getTargetUrl())->toContain('/login');

    expect(session()->get('errors')?->get('login'))
        ->toContain('Your sign-in session expired. Please sign in again.');
});

it('redirects a stale-CSRF browser POST /login/2fa back to the pending step', function () {
    $user = User::factory()->create();

    [$request, $session] = stalePost('/login/2fa');
    $session->put(PendingLoginService::SESSION_KEY, [
        'user_id' => $user->id,
        'email' => $user->email,
        'remember' => false,
        'step' => PendingLoginService::STEP_2FA,
        'attempts' => 0,
        'expires_at' => now()->addSeconds(PendingLoginService::TTL_SECONDS)->getTimestamp(),
    ]);

    $response = renderThroughHandler($request, new TokenMismatchException('CSRF token mismatch.'));

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toContain('step='.PendingLoginService::STEP_2FA);
});

it('leaves a JSON/XHR stale-CSRF POST /login as a 419 for the JSON handler', function () {
    $request = Request::create('/login', 'POST', ['_token' => 'stale-token']);
    $request->headers->set('Accept', 'application/json');
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');
    $request->setLaravelSession(app('session.store'));

    $response = renderThroughHandler($request, new TokenMismatchException('CSRF token mismatch.'));

    expect($response)->not->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getStatusCode())->toBe(419);
});

it('does not intercept a non-login stale-CSRF POST', function () {
    [$request] = stalePost('/some/other/form');

    $response = renderThroughHandler($request, new TokenMismatchException('CSRF token mismatch.'));

    expect($response)->not->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getStatusCode())->toBe(419);
});
