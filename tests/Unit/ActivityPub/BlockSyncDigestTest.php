<?php

use App\Services\BlockSyncService;

it('matches the digest example of the FEP', function () {
    expect(BlockSyncService::digest([
        ['actor' => 'https://a.example/users/alice', 'object' => 'https://b.example/users/bob'],
        ['actor' => 'https://a.example/users/carol', 'object' => 'https://b.example/users/bob'],
    ]))->toBe('5267015e810867e01494e494c2cbe378fe9bf05e4e5dba51d65f38cbe4a47dd5');
});

it('hashes each pair as actor, space, object', function () {
    expect(bin2hex(BlockSyncService::pairHash('https://a.example/users/alice', 'https://b.example/users/bob')))
        ->toBe('5a0f09e214e8951a292c01ce220e3b768e738ab28917c73a3ced82e8fd8c77c2');
});

it('hashes an empty set to zeros', function () {
    expect(BlockSyncService::digest([]))->toBe(str_repeat('0', 64));
});

it('does not depend on the order of the pairs', function () {
    $a = ['actor' => 'https://a.example/users/1', 'object' => 'https://b.example/users/1'];
    $b = ['actor' => 'https://a.example/users/2', 'object' => 'https://b.example/users/1'];

    expect(BlockSyncService::digest([$a, $b]))->toBe(BlockSyncService::digest([$b, $a]));
});

it('parses the example header of the FEP', function () {
    expect(BlockSyncService::parseHeader('url="https://a.example/federation/block-sync", digest="5267015e810867e01494e494c2cbe378fe9bf05e4e5dba51d65f38cbe4a47dd5"'))
        ->toBe([
            'url' => 'https://a.example/federation/block-sync',
            'digest' => '5267015e810867e01494e494c2cbe378fe9bf05e4e5dba51d65f38cbe4a47dd5',
        ]);
});

it('rejects malformed headers', function () {
    $digest = str_repeat('0', 64);

    expect(BlockSyncService::parseHeader(''))->toBeNull();
    expect(BlockSyncService::parseHeader('url="a", digest="abc"'))->toBeNull();
    expect(BlockSyncService::parseHeader('digest="'.$digest.'"'))->toBeNull();
    expect(BlockSyncService::parseHeader('url="a", url="b", digest="'.$digest.'"'))->toBeNull();
});

it('only reads Block items with an actor and an object', function () {
    expect(BlockSyncService::itemToPair([
        'type' => 'Block',
        'actor' => ['id' => 'https://a.example/users/alice'],
        'object' => 'https://b.example/users/bob',
    ]))->toBe(['actor' => 'https://a.example/users/alice', 'object' => 'https://b.example/users/bob']);

    expect(BlockSyncService::itemToPair(['type' => 'Follow', 'actor' => 'https://a.example/x', 'object' => 'https://b.example/y']))->toBeNull();
    expect(BlockSyncService::itemToPair(['type' => 'Block', 'actor' => 'https://a.example/x']))->toBeNull();
    expect(BlockSyncService::itemToPair('https://b.example/users/bob'))->toBeNull();
});

it('reads the collection digest in compact or expanded form', function () {
    $digest = '5267015e810867e01494e494c2cbe378fe9bf05e4e5dba51d65f38cbe4a47dd5';

    expect(BlockSyncService::collectionDigest(['blockSynchronizationDigest' => strtoupper($digest)]))->toBe($digest);
    expect(BlockSyncService::collectionDigest([BlockSyncService::DIGEST_CONTEXT_TERM => $digest]))->toBe($digest);
    expect(BlockSyncService::collectionDigest(['blockSynchronizationDigest' => 'abc']))->toBeNull();
    expect(BlockSyncService::collectionDigest([]))->toBeNull();
});
