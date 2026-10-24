<?php

use App\Services\Account\RemoteAuthService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| RemoteAuthService::isDomainCompatible
|--------------------------------------------------------------------------
|
| The compatibility check must fail closed (return false) on any malformed or
| non-JSON beagle response rather than throwing a TypeError, so the Sign-in
| with Mastodon flow soft-fails gracefully instead of 500ing.
|
*/

beforeEach(function () {
    Cache::flush();
});

it('returns false on a 200 non-json body instead of throwing', function () {
    Http::fake([
        'beagle.pixelfed.net/*' => Http::response('<html>Bad Gateway</html>', 200),
    ]);

    expect(RemoteAuthService::isDomainCompatible('mastodon.social'))->toBeFalse();
});

it('returns false on an empty body', function () {
    Http::fake([
        'beagle.pixelfed.net/*' => Http::response('', 200),
    ]);

    expect(RemoteAuthService::isDomainCompatible('example.social'))->toBeFalse();
});

it('returns false when beagle reports incompatible', function () {
    Http::fake([
        'beagle.pixelfed.net/*' => Http::response(['compatible' => false], 200),
    ]);

    expect(RemoteAuthService::isDomainCompatible('mastodon.example'))->toBeFalse();
});

it('returns true when beagle reports compatible', function () {
    Http::fake([
        'beagle.pixelfed.net/*' => Http::response(['compatible' => true], 200),
    ]);

    expect(RemoteAuthService::isDomainCompatible('good.example'))->toBeTrue();
});

it('redirect soft-fails gracefully on a non-json beagle body', function () {
    config(['remote-auth.mastodon.enabled' => true]);
    config(['remote-auth.mastodon.ignore_closed_state' => true]);

    // Let Helpers::validateUrl treat the domain as publicly resolvable without
    // a real DNS lookup so the flow reaches isDomainCompatible.
    Cache::put(
        'helpers:url:public-ips:'.hash('xxh128', 'mastodon.social'),
        ['203.0.113.40'],
        3600
    );

    Http::fake([
        'beagle.pixelfed.net/*' => Http::response('<html>Bad Gateway</html>', 200),
    ]);

    $res = $this->postJson('/auth/raw/mastodon/redirect', ['domain' => 'mastodon.social'])
        ->assertOk();

    expect($res->json('action'))->toBe('incompatible_domain')
        ->and($res->json('ready'))->toBeFalse();
});
