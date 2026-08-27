<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Routing Integration Tests
|--------------------------------------------------------------------------
|
| Verify that routes resolve correctly, named routes generate URLs,
| and the route middleware pipeline is intact.
|
*/

it('resolves named routes to URLs', function (string $name, string $contains) {
    expect(route($name))->toContain($contains);
})->with([
    'login' => ['login', '/login'],
    'register' => ['register', '/register'],
    'home timeline' => ['timeline.personal', '/'],
    'settings' => ['settings', '/settings/home'],
    'discover' => ['discover', '/discover'],
    'password.confirm' => ['password.confirm', '/i/auth/sudo'],
    'password.request' => ['password.request', '/password/reset'],
]);

it('does not have duplicate route names', function () {
    $routes = Route::getRoutes();
    $names = collect($routes->getRoutes())
        ->filter(fn ($route) => $route->getName())
        ->groupBy(fn ($route) => $route->getName())
        ->filter(fn ($group) => $group->count() > 1);

    expect($names->keys()->toArray())->toBeEmpty(
        'Duplicate route names found: '.implode(', ', $names->keys()->toArray())
    );
});

it('registers api routes under the api prefix', function () {
    $apiRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'api/'));

    expect($apiRoutes->count())->toBeGreaterThan(50);
});

it('registers oauth routes under the oauth prefix', function () {
    $oauthRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'oauth/'));

    expect($oauthRoutes->count())->toBeGreaterThan(5);
});

it('web routes use the web middleware group', function () {
    $route = Route::getRoutes()->getByName('login');

    expect($route->gatherMiddleware())->toContain('web');
});
