<?php

use App\Jobs\StoryPipeline\StoryFetch;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| StoryFetch SSRF hardening
|--------------------------------------------------------------------------
|
| Both outbound fetches (bearcap story JSON and media download) route through
| SecureMediaFetchService, which refuses to follow redirects to private /
| reserved addresses. These tests exercise the payload-fetch sink directly.
|
*/

function seedPublicIpForStory(string $host): void
{
    Cache::put('helpers:url:public-ips:'.hash('xxh128', $host), ['203.0.113.60'], 3600);
}

/**
 * Invoke the private fetchStoryPayload() via reflection.
 */
function callFetchStoryPayload(string $url, string $token)
{
    $job = new StoryFetch([]);
    $ref = new ReflectionMethod($job, 'fetchStoryPayload');
    $ref->setAccessible(true);

    return $ref->invoke($job, $url, $token);
}

beforeEach(function () {
    Cache::flush();
});

it('fetches the story payload over the hardened path', function () {
    seedPublicIpForStory('peer.example');

    Http::fake([
        'https://peer.example/story' => Http::response('{"id":"https://peer.example/s/1"}', 200, [
            'Content-Type' => 'application/json',
        ]),
    ]);

    $payload = callFetchStoryPayload('https://peer.example/story', 'bearcap-token-1234567890');

    expect($payload)->toBeArray()
        ->and($payload['id'])->toBe('https://peer.example/s/1');
});

it('refuses a payload fetch that redirects to a private address', function () {
    seedPublicIpForStory('peer.example');

    Http::fake([
        'https://peer.example/story' => Http::response('', 302, [
            'Location' => 'http://169.254.169.254/latest/meta-data/',
        ]),
        '169.254.169.254/*' => Http::response('SECRET', 200),
    ]);

    $payload = callFetchStoryPayload('https://peer.example/story', 'bearcap-token-1234567890');

    expect($payload)->toBeNull();

    Http::assertNotSent(function ($request) {
        return str_contains($request->url(), '169.254.169.254');
    });
});
