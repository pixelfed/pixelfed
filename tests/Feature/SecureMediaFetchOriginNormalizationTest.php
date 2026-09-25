<?php

use App\Services\SecureMediaFetchService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| SecureMediaFetchService origin-host normalization
|--------------------------------------------------------------------------
|
| The origin host used to decide whether caller headers may be forwarded was
| parsed from the raw, pre-validation url and only lowercased. Helpers::
| validateUrl() normalizes the host further (trailing dot, IDN punycode), so
| a legitimate same-origin request whose input host differed only by a
| trailing dot or case had its Authorization header wrongly stripped. The
| origin host must be captured from the validated hop, using the same
| normalization applied on every hop.
|
*/

function seedPublicIpForOrigin(string $host): void
{
    Cache::put('helpers:url:public-ips:'.hash('xxh128', $host), ['203.0.113.50'], 3600);
}

beforeEach(function () {
    Cache::flush();
});

it('forwards caller headers when the input host has a trailing dot', function () {
    seedPublicIpForOrigin('pixelfed.org');

    Http::fake([
        'https://pixelfed.org/story' => Http::response('{"ok":true}', 200, [
            'Content-Type' => 'application/json',
        ]),
    ]);

    // Trailing-dot FQDN: normalizes to pixelfed.org, same origin as the hop.
    $body = SecureMediaFetchService::get('https://pixelfed.org./story', null, null, [
        'Authorization' => 'Bearer secret-token',
    ]);

    expect($body)->toBe('{"ok":true}');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://pixelfed.org/story'
            && $request->hasHeader('Authorization', 'Bearer secret-token');
    });
});

it('forwards caller headers when the input host case differs', function () {
    seedPublicIpForOrigin('pixelfed.org');

    Http::fake([
        'https://pixelfed.org/story' => Http::response('{"ok":true}', 200, [
            'Content-Type' => 'application/json',
        ]),
    ]);

    $body = SecureMediaFetchService::get('https://PixelFed.ORG/story', null, null, [
        'Authorization' => 'Bearer secret-token',
    ]);

    expect($body)->toBe('{"ok":true}');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://pixelfed.org/story'
            && $request->hasHeader('Authorization', 'Bearer secret-token');
    });
});

it('still strips caller headers on a genuine cross-origin redirect', function () {
    seedPublicIpForOrigin('pixelfed.org');
    seedPublicIpForOrigin('joinloops.org');

    Http::fake([
        'https://pixelfed.org/story' => Http::response('', 302, [
            'Location' => 'https://joinloops.org/story',
        ]),
        'https://joinloops.org/story' => Http::response('{"ok":true}', 200, [
            'Content-Type' => 'application/json',
        ]),
    ]);

    $body = SecureMediaFetchService::get('https://pixelfed.org./story', null, null, [
        'Authorization' => 'Bearer secret-token',
    ]);

    expect($body)->toBe('{"ok":true}');

    Http::assertSent(function ($request) {
        if ($request->url() !== 'https://joinloops.org/story') {
            return false;
        }

        return ! $request->hasHeader('Authorization');
    });
});
