<?php

use App\Services\SecureMediaFetchService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

function seedPublicIpForProbe(string $host): void
{
    Cache::put('helpers:url:public-ips:'.hash('xxh128', $host), ['203.0.113.50'], 3600);
}

beforeEach(function () {
    Cache::flush();
});

it('falls back to a ranged GET when a presigned redirect rejects HEAD', function () {
    seedPublicIpForProbe('pixelfed.org');
    seedPublicIpForProbe('joinloops.org');

    Http::fake(function ($request) {
        if ($request->url() === 'https://pixelfed.org/avatar.jpeg') {
            return Http::response('', 302, [
                'Location' => 'https://joinloops.org/avatar.jpeg?X-Amz-Signature=abc',
            ]);
        }

        if ($request->method() === 'HEAD') {
            return Http::response('', 403);
        }

        return Http::response('x', 206, [
            'Content-Type' => 'image/jpeg',
            'Content-Range' => 'bytes 0-0/54321',
            'Content-Length' => '1',
        ]);
    });

    $head = SecureMediaFetchService::head('https://pixelfed.org/avatar.jpeg', 2000000);

    expect($head)->toBe(['length' => 54321, 'mime' => 'image/jpeg']);

    Http::assertSent(function ($request) {
        return $request->method() === 'GET'
            && $request->url() === 'https://joinloops.org/avatar.jpeg?X-Amz-Signature=abc'
            && $request->hasHeader('Range', 'bytes=0-0');
    });
});

it('uses the full length when the probe target ignores the range header', function () {
    seedPublicIpForProbe('pixelfed.org');

    Http::fake(function ($request) {
        if ($request->method() === 'HEAD') {
            return Http::response('', 200, ['Content-Type' => 'image/png']);
        }

        return Http::response('body', 200, [
            'Content-Type' => 'image/png',
            'Content-Length' => '4321',
        ]);
    });

    $head = SecureMediaFetchService::head('https://pixelfed.org/avatar.png', 2000000);

    expect($head)->toBe(['length' => 4321, 'mime' => 'image/png']);
});

it('rejects a probe that reports a size over the limit', function () {
    seedPublicIpForProbe('pixelfed.org');

    Http::fake(function ($request) {
        if ($request->method() === 'HEAD') {
            return Http::response('', 405);
        }

        return Http::response('x', 206, [
            'Content-Type' => 'image/jpeg',
            'Content-Range' => 'bytes 0-0/9000000',
            'Content-Length' => '1',
        ]);
    });

    expect(SecureMediaFetchService::head('https://pixelfed.org/avatar.jpeg', 2000000))->toBeFalse();
});

it('does not probe when HEAD already returns a length and type', function () {
    seedPublicIpForProbe('pixelfed.org');

    Http::fake([
        'https://pixelfed.org/avatar.jpeg' => Http::response('', 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Length' => '1234',
        ]),
    ]);

    $head = SecureMediaFetchService::head('https://pixelfed.org/avatar.jpeg', 2000000);

    expect($head)->toBe(['length' => 1234, 'mime' => 'image/jpeg']);

    Http::assertSentCount(1);
});
