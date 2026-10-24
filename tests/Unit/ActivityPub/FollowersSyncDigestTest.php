<?php

use App\Services\FollowersSyncService;

/*
|--------------------------------------------------------------------------
| FEP-8fcf primitives
|--------------------------------------------------------------------------
*/

it('matches the digest test vector of the FEP', function () {
    expect(FollowersSyncService::digest([
        'https://testing.example.org/users/1',
        'https://testing.example.org/users/2',
    ]))->toBe('c33f48cd341ef046a206b8a72ec97af65079f9a3a9b90eef79c5920dce45c61f');
});

it('hashes an empty collection to zeros', function () {
    expect(FollowersSyncService::digest([]))->toBe(FollowersSyncService::EMPTY_DIGEST);
});

it('does not depend on the order of the followers', function () {
    expect(FollowersSyncService::digest(['a', 'b', 'c']))
        ->toBe(FollowersSyncService::digest(['c', 'a', 'b']));
});

it('normalizes authorities', function () {
    expect(FollowersSyncService::authority('HTTPS://Example.ORG:443/users/1'))->toBe('https://example.org');
    expect(FollowersSyncService::authority('https://example.org:8443/users/1'))->toBe('https://example.org:8443');
    expect(FollowersSyncService::authority('http://example.org/users/1'))->toBe('http://example.org');
    expect(FollowersSyncService::authority('https://testing.example.org/users/1'))->not->toBe('https://example.org');
});

it('rejects urls that have no usable authority', function () {
    expect(FollowersSyncService::authority('ftp://example.org/x'))->toBeNull();
    expect(FollowersSyncService::authority('https://user:pass@example.org/x'))->toBeNull();
    expect(FollowersSyncService::authority('not a url'))->toBeNull();
    expect(FollowersSyncService::authority(null))->toBeNull();
});

it('parses the example header of the FEP', function () {
    $header = 'collectionId="https://example.org/users/1/followers", url="https://example.org/users/1/followers_synchronization", digest="c33f48cd341ef046a206b8a72ec97af65079f9a3a9b90eef79c5920dce45c61f"';

    expect(FollowersSyncService::parseHeader($header))->toBe([
        'collectionId' => 'https://example.org/users/1/followers',
        'url' => 'https://example.org/users/1/followers_synchronization',
        'digest' => 'c33f48cd341ef046a206b8a72ec97af65079f9a3a9b90eef79c5920dce45c61f',
    ]);
});

it('rejects malformed headers', function () {
    $digest = FollowersSyncService::EMPTY_DIGEST;

    expect(FollowersSyncService::parseHeader(''))->toBeNull();
    expect(FollowersSyncService::parseHeader('collectionId="a", url="b", digest="abc"'))->toBeNull();
    expect(FollowersSyncService::parseHeader('collectionId="a", digest="'.$digest.'"'))->toBeNull();
    expect(FollowersSyncService::parseHeader('collectionId="a", url="b", url="c", digest="'.$digest.'"'))->toBeNull();
});

it('only accepts a followers collection on the authority of its actor', function () {
    expect(FollowersSyncService::followersUrlFromActor([
        'id' => 'https://remote.example/users/alice',
        'followers' => 'https://remote.example/users/alice/followers',
    ]))->toBe('https://remote.example/users/alice/followers');

    expect(FollowersSyncService::followersUrlFromActor([
        'id' => 'https://remote.example/users/alice',
        'followers' => 'https://elsewhere.example/followers',
    ]))->toBeNull();

    expect(FollowersSyncService::followersUrlFromActor([
        'id' => 'https://remote.example/users/alice',
    ]))->toBeNull();
});
