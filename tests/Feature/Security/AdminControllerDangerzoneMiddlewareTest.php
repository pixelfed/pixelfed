<?php

use App\Http\Controllers\AdminCuratedRegisterController;
use App\Http\Controllers\AdminShadowFilterController;
use App\Http\Controllers\PageController;

/*
|--------------------------------------------------------------------------
| Admin controllers must enforce sudo mode
|--------------------------------------------------------------------------
|
| AdminController applies the `dangerzone` (sudo mode / RequirePassword)
| middleware to every admin route. Controllers that expose equally sensitive
| admin operations must do the same so a stolen long-lived session cannot be
| used to modify pages, shadow-filter users, or approve registrations without
| a recent password confirmation.
|
*/

function controllerMiddleware(object $controller): array
{
    return collect($controller->getMiddleware())
        ->pluck('middleware')
        ->all();
}

it('requires dangerzone middleware on PageController', function () {
    expect(controllerMiddleware(new PageController))->toContain('dangerzone');
});

it('requires dangerzone middleware on AdminShadowFilterController', function () {
    expect(controllerMiddleware(new AdminShadowFilterController))->toContain('dangerzone');
});

it('requires dangerzone middleware on AdminCuratedRegisterController', function () {
    expect(controllerMiddleware(new AdminCuratedRegisterController))->toContain('dangerzone');
});
