<?php

use App\Http\Controllers\AppRegisterController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| AppRegisterController onboarding username normalization - regression
|--------------------------------------------------------------------------
|
| onboarding() took the username raw and validated/stored it case-sensitively.
| On PostgreSQL (case-sensitive unique index) that let `Alice` register
| alongside an existing `alice`, defeating the lowercase-username contract
| every other signup path enforces. Registration input must be lowercased on
| pgsql before validation and storage.
|
| db_is_pgsql() resolves the real driver from config('database.default'), so
| swapping the default connection exercises each branch without a live server
| (the normalization runs before any query). Mirrors SearchOperatorTest.
|
*/

function normalizeOnboardingInput(string $driver, array $input): array
{
    config(['database.default' => $driver]);

    $controller = app(AppRegisterController::class);
    $request = Request::create('/api/auth/onboarding', 'POST', $input);

    $m = new ReflectionMethod($controller, 'normalizeRegistrationInput');
    $m->setAccessible(true);
    $m->invoke($controller, $request);

    return [
        'username' => $request->input('username'),
        'email' => $request->input('email'),
    ];
}

it('lowercases username and email on postgres (REGRESSION)', function () {
    $out = normalizeOnboardingInput('pgsql', [
        'username' => 'Alice',
        'email' => 'Alice@Example.com',
    ]);

    expect($out['username'])->toBe('alice');
    expect($out['email'])->toBe('alice@example.com');
});

it('leaves username untouched on mysql', function () {
    $out = normalizeOnboardingInput('mysql', [
        'username' => 'Alice',
        'email' => 'Alice@Example.com',
    ]);

    expect($out['username'])->toBe('Alice');
});

it('leaves username untouched on sqlite', function () {
    $out = normalizeOnboardingInput('sqlite', [
        'username' => 'Alice',
        'email' => 'Alice@Example.com',
    ]);

    expect($out['username'])->toBe('Alice');
});
