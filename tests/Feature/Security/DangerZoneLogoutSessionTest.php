<?php

use App\Http\Middleware\DangerZone;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| DangerZone forced-logout clears 2FA session state
|--------------------------------------------------------------------------
|
| When a user is logged out for exceeding sudo-mode attempts, the middleware
| must clear security-related session flags. A lingering 2fa.session.active
| would let the next login on the same session skip the 2FA checkpoint.
|
| Note: this middleware class is not currently wired to the `dangerzone`
| alias (which maps to Laravel's RequirePassword), so this exercises the
| class directly to guard its logic should it be reused.
|
*/

it('clears 2fa.session.active when logging out for excessive sudo attempts', function () {
    $user = User::factory()->create();
    $user->refresh();

    $request = Request::create('/settings/email', 'GET');
    $session = new Store('test', new ArraySessionHandler(120));
    $request->setLaravelSession($session);
    $request->setUserResolver(fn () => $user);

    $session->put('sudoModeAttempts', 4);
    $session->put('2fa.session.active', true);
    $session->put('sudoMode', now()->timestamp);

    $response = (new DangerZone)->handle($request, function ($req) {
        return response('passed-through', 200);
    });

    expect($response->isRedirect())->toBeTrue();
    expect($session->has('2fa.session.active'))->toBeFalse();
    expect($session->has('sudoMode'))->toBeFalse();
    expect($session->has('sudoModeAttempts'))->toBeFalse();
});
