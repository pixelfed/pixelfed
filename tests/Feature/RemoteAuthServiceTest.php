<?php

use App\Models\RemoteAuthInstance;
use App\Services\Account\RemoteAuthService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

function activeRemoteAuthInstance(string $domain = 'mastodon.example'): RemoteAuthInstance
{
    return RemoteAuthInstance::create([
        'domain' => $domain,
        'client_id' => 'cid',
        'client_secret' => 'secret',
        'redirect_uri' => url('/auth/mastodon/callback'),
        'active' => true,
        'banned' => false,
    ]);
}

it('returns false on connection failure when verifying credentials', function () {
    activeRemoteAuthInstance();

    Http::fake(function () {
        throw new ConnectionException('timed out');
    });

    $res = RemoteAuthService::getVerifyCredentials('mastodon.example', 'token');

    expect($res)->toBeFalse();
});

it('returns false on server error when verifying credentials', function () {
    activeRemoteAuthInstance();

    Http::fake([
        '*' => Http::response('nope', 500),
    ]);

    $res = RemoteAuthService::getVerifyCredentials('mastodon.example', 'token');

    expect($res)->toBeFalse();
});

it('returns json on success when verifying credentials', function () {
    activeRemoteAuthInstance();

    Http::fake([
        '*' => Http::response(['acct' => 'alice', 'id' => '1'], 200),
    ]);

    $res = RemoteAuthService::getVerifyCredentials('mastodon.example', 'token');

    expect($res)->toBeArray();
    expect($res['acct'])->toBe('alice');
});

it('returns false on connection failure when getting following', function () {
    activeRemoteAuthInstance();

    Http::fake(function () {
        throw new ConnectionException('timed out');
    });

    $res = RemoteAuthService::getFollowing('mastodon.example', 'token', 42);

    expect($res)->toBeFalse();
});

it('returns false on connection failure when getting a token', function () {
    activeRemoteAuthInstance();

    Http::fake(function () {
        throw new ConnectionException('timed out');
    });

    $res = RemoteAuthService::getToken('mastodon.example', 'code');

    expect($res)->toBeFalse();
});
