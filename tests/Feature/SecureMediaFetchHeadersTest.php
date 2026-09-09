<?php

use App\Services\SecureMediaFetchService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| SecureMediaFetchService header passthrough + redirect safety
|--------------------------------------------------------------------------
|
| get() accepts caller headers (e.g. an Authorization bearer for bearcap
| fetches) but must forward them only to the original host and strip them on
| cross-origin redirects. Redirects to private/reserved addresses must be
| refused (never connected to).
|
*/

function seedPublicIp(string $host): void
{
    Cache::put('helpers:url:public-ips:'.hash('xxh128', $host), ['203.0.113.50'], 3600);
}

beforeEach(function () {
    Cache::flush();
});

it('forwards caller headers to the origin host', function () {
    seedPublicIp('origin.example');

    Http::fake([
        'https://origin.example/story' => Http::response('{"ok":true}', 200, [
            'Content-Type' => 'application/json',
        ]),
    ]);

    $body = SecureMediaFetchService::get('https://origin.example/story', null, null, [
        'Authorization' => 'Bearer secret-token',
    ]);

    expect($body)->toBe('{"ok":true}');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://origin.example/story'
            && $request->hasHeader('Authorization', 'Bearer secret-token');
    });
});

it('strips the Authorization header on a cross-origin redirect', function () {
    seedPublicIp('origin.example');
    seedPublicIp('other.example');

    Http::fake([
        'https://origin.example/story' => Http::response('', 302, [
            'Location' => 'https://other.example/story',
        ]),
        'https://other.example/story' => Http::response('{"ok":true}', 200, [
            'Content-Type' => 'application/json',
        ]),
    ]);

    $body = SecureMediaFetchService::get('https://origin.example/story', null, null, [
        'Authorization' => 'Bearer secret-token',
    ]);

    expect($body)->toBe('{"ok":true}');

    // The cross-origin hop must NOT carry the bearer token.
    Http::assertSent(function ($request) {
        if ($request->url() !== 'https://other.example/story') {
            return false;
        }

        return ! $request->hasHeader('Authorization');
    });
});

it('refuses to follow a redirect to a private address', function () {
    seedPublicIp('origin.example');

    Http::fake([
        'https://origin.example/story' => Http::response('', 302, [
            'Location' => 'http://169.254.169.254/latest/meta-data/',
        ]),
        // If the service (incorrectly) followed, this would answer; it must not.
        '169.254.169.254/*' => Http::response('SECRET', 200),
    ]);

    $body = SecureMediaFetchService::get('https://origin.example/story');

    expect($body)->toBeFalse();

    Http::assertNotSent(function ($request) {
        return str_contains($request->url(), '169.254.169.254');
    });
});
