<?php

use App\Services\SecureMediaFetchService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| SecureMediaFetchService Guzzle client option compatibility
|--------------------------------------------------------------------------
|
| Guzzle 8's curl handler rejects CURLOPT_PROTOCOLS / CURLOPT_REDIR_PROTOCOLS
| passed inside the 'curl' option, and its stream handler rejects any
| non-empty 'curl' option. Either throws before network I/O, so the whole
| fetcher silently fails closed. These assertions inspect the exact option
| array the service builds and guard against that arrangement returning.
|
| Http::fake() swaps in a mock handler that never performs the real handler
| selection or option validation, which is why the production break slipped
| past the fake-based feature tests. So we assert on the built options
| directly rather than relying on a faked round trip.
|
*/

function buildSecureFetchOptions(): array
{
    $service = new SecureMediaFetchService;

    $method = new ReflectionMethod($service, 'requestOptions');
    $method->setAccessible(true);

    return $method->invoke(
        $service,
        'pixelfed.org',
        443,
        ['203.0.113.50'],
        2000000,
    );
}

it('does not pass the forbidden curl protocol constants Guzzle 8 rejects', function () {
    $options = buildSecureFetchOptions();

    expect($options['curl'])->not->toHaveKey(CURLOPT_PROTOCOLS);
    expect($options['curl'])->not->toHaveKey(CURLOPT_REDIR_PROTOCOLS);
});

it('restricts the scheme to https via the native protocols option', function () {
    $options = buildSecureFetchOptions();

    expect($options['protocols'])->toBe(['https']);
    expect($options['allow_redirects'])->toBeFalse();
});

it('keeps the resolve pin and connection-reuse hardening', function () {
    $options = buildSecureFetchOptions();

    expect($options['curl'][CURLOPT_RESOLVE])->toBe(['pixelfed.org:443:203.0.113.50']);
    expect($options['curl'][CURLOPT_FRESH_CONNECT])->toBeTrue();
    expect($options['curl'][CURLOPT_FORBID_REUSE])->toBeTrue();
});

it('never combines stream mode with a non-empty curl option', function () {
    $options = buildSecureFetchOptions();

    // Guzzle 8's stream handler rejects any non-empty curl option, so the
    // CURLOPT_RESOLVE pin can only ride a buffered (non-stream) request.
    expect($options['curl'])->not->toBeEmpty();
    expect($options['stream'])->toBeFalse();
});

it('issues the ranged probe GET without stream mode', function () {
    Cache::flush();
    Cache::put('helpers:url:public-ips:'.hash('xxh128', 'pixelfed.org'), ['203.0.113.50'], 3600);

    Http::fake(function ($request) {
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
            && $request->hasHeader('Range', 'bytes=0-0');
    });
});
