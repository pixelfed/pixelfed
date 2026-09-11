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
| DangerZone OIDC bypass is per-user
|--------------------------------------------------------------------------
|
| The DangerZone middleware must only skip sudo mode for OIDC-registered users
| (who have a random unknown password), not for every user on an OIDC-enabled
| instance. Local users have real passwords and must still confirm sudo mode.
|
| Note: this middleware class is not currently wired to the `dangerzone` alias
| (which maps to Laravel's RequirePassword), so this exercises the class
| directly to guard its logic should it be reused.
|
*/

function runDangerZone(User $user): mixed
{
    config(['remote-auth.oidc.enabled' => true]);

    $request = Request::create('/settings/security', 'GET');
    $request->setLaravelSession(new Store('test', new ArraySessionHandler(120)));
    $request->setUserResolver(fn () => $user);

    return (new DangerZone)->handle($request, function ($req) {
        return response('passed-through', 200);
    });
}

it('still requires sudo mode for a local user on an OIDC instance', function () {
    $user = User::factory()->create(['register_source' => 'web']);
    $user->refresh();

    $response = runDangerZone($user);

    expect($response->isRedirect())->toBeTrue();
    expect($response->headers->get('Location'))->toContain('/i/auth/sudo');
});

it('bypasses sudo mode for an OIDC-registered user', function () {
    $user = User::factory()->create(['register_source' => 'oidc']);
    $user->refresh();

    $response = runDangerZone($user);

    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toBe('passed-through');
});
