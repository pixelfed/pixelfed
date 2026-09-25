<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| JSON exception handler must redact non-HTTP errors in production
|--------------------------------------------------------------------------
|
| The catch-all JSON render callback returned $e->getMessage() for every
| throwable regardless of APP_DEBUG, leaking internal messages (PHP errors,
| SQLSTATE, paths) to any JSON/ActivityPub client. Non-HTTP exceptions must
| redact to "Server Error" when app.debug is false, mirroring Laravel.
|
*/

beforeEach(function () {
    Route::get('__test/boom', function () {
        throw new RuntimeException('sensitive internal detail: /var/www/secret.php');
    });
});

it('redacts non-HTTP exception messages for JSON clients in production', function () {
    config(['app.debug' => false]);

    $response = $this->get('/__test/boom', ['Accept' => 'application/json']);

    $response->assertStatus(500);
    $payload = $response->json();

    expect($payload)->toHaveKey('error');
    expect($payload['error'])->toBe('Server Error');
    expect($response->getContent())->not->toContain('sensitive internal detail');
});

it('surfaces the real message for JSON clients when debug is enabled', function () {
    config(['app.debug' => true]);

    $response = $this->get('/__test/boom', ['Accept' => 'application/json']);

    $response->assertStatus(500);
    expect($response->json('error'))->toContain('sensitive internal detail');
});

it('redacts for ActivityPub Accept headers too', function () {
    config(['app.debug' => false]);

    $response = $this->get('/__test/boom', ['Accept' => 'application/activity+json']);

    $response->assertStatus(500);
    expect($response->json('error'))->toBe('Server Error');
});
