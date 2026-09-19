<?php

use App\Http\Controllers\Api\v2026\Admin\ConfigCacheController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| v2026 admin config route + controller pinning (Task 17)
|--------------------------------------------------------------------------
|
| These tests pin the SHAPE of the v2026 admin config API against the route
| collection, guarding against a revert of the post-Task-14 restructure that
| moved the controller to App\Http\Controllers\Api\v2026\Admin\ConfigCache and
| repathed the routes under /api/v2026/admin/config.
|
| They assert the controller class + method behind each verb/path, the '.*'
| where-constraint on the {key} segment, that the OLD pre-move paths are gone,
| and that the OLD deleted controller class no longer exists.
|
| Validates: Requirements 5.1
|
*/

const ROUTING_CONTROLLER = ConfigCacheController::class;

/**
 * Find the registered route matching an exact URI + HTTP method, or null.
 */
function routingFind(string $uri, string $method): ?Illuminate\Routing\Route
{
    foreach (Route::getRoutes() as $route) {
        if ($route->uri() === $uri && in_array($method, $route->methods(), true)) {
            return $route;
        }
    }

    return null;
}

test('GET api/v2026/admin/config maps to ConfigCache@showBulk', function () {
    $route = routingFind('api/v2026/admin/config', 'GET');

    expect($route)->not->toBeNull();
    expect($route->getActionName())->toBe(ROUTING_CONTROLLER.'@showBulk');
    expect($route->getAction('controller'))->toBe(ROUTING_CONTROLLER.'@showBulk');
});

test('POST api/v2026/admin/config maps to ConfigCache@updateBulk', function () {
    $route = routingFind('api/v2026/admin/config', 'POST');

    expect($route)->not->toBeNull();
    expect($route->getActionName())->toBe(ROUTING_CONTROLLER.'@updateBulk');
});

test('GET api/v2026/admin/config/{key} maps to ConfigCache@show with a dotted-key constraint', function () {
    $route = routingFind('api/v2026/admin/config/{key}', 'GET');

    expect($route)->not->toBeNull();
    expect($route->getActionName())->toBe(ROUTING_CONTROLLER.'@show');
    expect($route->wheres['key'] ?? null)->toBe('.*');
});

test('POST api/v2026/admin/config/{key} maps to ConfigCache@update with a dotted-key constraint', function () {
    $route = routingFind('api/v2026/admin/config/{key}', 'POST');

    expect($route)->not->toBeNull();
    expect($route->getActionName())->toBe(ROUTING_CONTROLLER.'@update');
    expect($route->wheres['key'] ?? null)->toBe('.*');
});


test('the OLD pre-move api/v2026/config paths are NOT registered', function () {
    expect(routingFind('api/v2026/config', 'GET'))->toBeNull();
    expect(routingFind('api/v2026/config', 'POST'))->toBeNull();
    expect(routingFind('api/v2026/config/{key}', 'GET'))->toBeNull();
    expect(routingFind('api/v2026/config/{key}', 'POST'))->toBeNull();
});

test('the v2026 ConfigCache controller class exists and the old AdminConfigController does not', function () {
    expect(class_exists(ROUTING_CONTROLLER))->toBeTrue();
    expect(class_exists('App\Http\Controllers\Api\AdminConfigController'))->toBeFalse();
});
