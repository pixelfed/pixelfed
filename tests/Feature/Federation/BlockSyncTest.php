<?php

use App\Jobs\Federation\BlockSyncPipeline;
use App\Jobs\Federation\DeliverBlockActivity;
use App\Models\Follower;
use App\Models\Instance;
use App\Models\InstanceActor;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserFilter;
use App\Services\ActivityPubDeliveryService;
use App\Services\BlockSyncService;
use App\Util\ActivityPub\Inbox;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Redis::spy();
    Queue::fake();

    config([
        'instance.enable_cc' => false,
        'federation.activitypub.enabled' => true,
        'federation.activitypub.block_sync.enabled' => true,
        'federation.activitypub.block_sync.disclose' => true,
    ]);
});

function bsyncLocalProfile(): Profile
{
    $user = User::factory()->create();
    $user->refresh();

    return $user->profile;
}

function bsyncRemoteProfile(string $domain, string $username, array $attributes = []): Profile
{
    $actor = "https://{$domain}/users/{$username}";

    return Profile::factory()->remote()->create(array_merge([
        'domain' => $domain,
        'username' => "@{$username}@{$domain}",
        'remote_url' => $actor,
        'inbox_url' => "{$actor}/inbox",
        'sharedInbox' => "https://{$domain}/inbox",
        'key_id' => "{$actor}#main-key",
        'last_fetched_at' => now(),
    ], $attributes));
}

function bsyncBlock(Profile $blocker, Profile $blocked, int $ageInMinutes = 120): void
{
    DB::table('user_filters')->insert([
        'user_id' => $blocker->id,
        'filterable_id' => $blocked->id,
        'filterable_type' => Profile::class,
        'filter_type' => 'block',
        'created_at' => now()->subMinutes($ageInMinutes),
        'updated_at' => now()->subMinutes($ageInMinutes),
    ]);
}

function bsyncBlocks(Profile $blocker, Profile $blocked): bool
{
    return UserFilter::whereUserId($blocker->id)
        ->whereFilterableId($blocked->id)
        ->whereFilterableType(Profile::class)
        ->whereFilterType('block')
        ->exists();
}

function bsyncPair(Profile $blocker, Profile $blocked): array
{
    return ['actor' => $blocker->permalink(), 'object' => $blocked->permalink()];
}

function bsyncSeedHosts(array $hosts): void
{
    foreach ($hosts as $host) {
        Cache::put('helpers:url:public-ips:'.hash('xxh128', $host), ['203.0.113.40'], 3600);
    }

    Cache::put('instances:banned:domains', [], 1209600);
    Cache::put('instances:unlisted:domains', [], 1209600);
}

function bsyncKeyPair(): array
{
    $key = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);

    openssl_pkey_export($key, $private);

    return [$private, openssl_pkey_get_details($key)['key']];
}

function bsyncInProduction(callable $fn): mixed
{
    $app = app();
    $previous = $app['env'];
    $app['env'] = 'production';

    try {
        return $fn();
    } finally {
        $app['env'] = $previous;
    }
}

function bsyncSignedGetHeaders(string $privateKey, string $keyId, string $path, string $host = 'pixelfed.test'): array
{
    $date = now()->toRfc7231String();

    openssl_sign(
        "(request-target): get {$path}\nhost: {$host}\ndate: {$date}",
        $signature,
        $privateKey,
        OPENSSL_ALGO_SHA256
    );

    return [
        'Accept' => 'application/activity+json',
        'Date' => $date,
        'Signature' => sprintf(
            'keyId="%s",algorithm="rsa-sha256",headers="(request-target) host date",signature="%s"',
            $keyId,
            base64_encode($signature)
        ),
    ];
}

function bsyncInboundHeaders(Profile $sender, string $url, string $digest, bool $signed = true): array
{
    $list = '(request-target) host date digest'.($signed ? ' block-synchronization' : '');

    return [
        'signature' => [
            sprintf('keyId="%s",algorithm="rsa-sha256",headers="%s",signature="dGVzdA=="', $sender->key_id, $list),
        ],
        'block-synchronization' => [
            sprintf('url="%s", digest="%s"', $url, $digest),
        ],
    ];
}

function bsyncFakeCollection(string $url, array $pairs, string|false|null $digest = null): void
{
    [$private] = bsyncKeyPair();
    Cache::forever(InstanceActor::PKI_PRIVATE, $private);

    $document = [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => $url,
        'type' => 'OrderedCollection',
        'totalItems' => count($pairs),
        'orderedItems' => array_map(fn (array $pair) => ['type' => 'Block'] + $pair, $pairs),
    ];

    if ($digest !== false) {
        $document['blockSynchronizationDigest'] = $digest ?? BlockSyncService::digest($pairs);
    }

    Http::fake([
        $url => Http::response(json_encode($document), 200, ['Content-Type' => 'application/activity+json']),
    ]);
}

describe('sender', function () {
    it('signs a Block-Synchronization header scoped to each destination', function () {
        Http::fake();

        $local = bsyncLocalProfile();
        $alice = bsyncRemoteProfile('joinloops.org', 'alice');
        $carol = bsyncRemoteProfile('remote2.example', 'carol');

        bsyncBlock($local, $alice);
        bsyncBlock($local, $carol);
        bsyncSeedHosts(['joinloops.org', 'remote2.example', 'remote3.example']);

        bsyncInProduction(fn () => ActivityPubDeliveryService::pool(
            $local,
            [
                'https://joinloops.org/inbox',
                'https://remote2.example/inbox',
                'https://remote3.example/inbox',
            ],
            ['id' => $local->permalink('#create'), 'type' => 'Create', 'actor' => $local->permalink()]
        ));

        $expected = [
            'https://joinloops.org/inbox' => BlockSyncService::digest([bsyncPair($local, $alice)]),
            'https://remote2.example/inbox' => BlockSyncService::digest([bsyncPair($local, $carol)]),
            'https://remote3.example/inbox' => BlockSyncService::digest([]),
        ];

        Http::assertSentCount(3);

        foreach (Http::recorded() as [$request]) {
            $params = BlockSyncService::parseHeader($request->header('Block-Synchronization')[0] ?? null);

            preg_match('/headers="([^"]*)"/', $request->header('Signature')[0] ?? '', $signed);

            expect($params)->not->toBeNull();
            expect($params['url'])->toBe(BlockSyncService::endpointUrl());
            expect($params['digest'])->toBe($expected[$request->url()]);
            expect(explode(' ', $signed[1] ?? ''))->toContain('block-synchronization');
        }
    });

    it('does not reveal anything unless disclosure is enabled', function () {
        config(['federation.activitypub.block_sync.disclose' => false]);
        Http::fake();

        $local = bsyncLocalProfile();
        bsyncBlock($local, bsyncRemoteProfile('joinloops.org', 'alice'));
        bsyncSeedHosts(['joinloops.org']);

        bsyncInProduction(fn () => ActivityPubDeliveryService::pool(
            $local,
            ['https://joinloops.org/inbox'],
            ['id' => $local->permalink('#create'), 'type' => 'Create', 'actor' => $local->permalink()]
        ));

        Http::assertSent(fn ($request) => empty($request->header('Block-Synchronization')));

        $this->get('/f/block_sync')->assertNotFound();
    });

    it('keeps the cached digests in step with blocks', function () {
        $local = bsyncLocalProfile();
        $alice = bsyncRemoteProfile('joinloops.org', 'alice');
        bsyncSeedHosts(['joinloops.org']);

        expect(BlockSyncService::outboundDigests())->toBe([]);

        $filter = UserFilter::create([
            'user_id' => $local->id,
            'filterable_id' => $alice->id,
            'filterable_type' => Profile::class,
            'filter_type' => 'block',
        ]);

        expect(BlockSyncService::outboundDigests())->toBe([
            'https://joinloops.org' => BlockSyncService::digest([bsyncPair($local, $alice)]),
        ]);

        $filter->delete();

        expect(BlockSyncService::outboundDigests())->toBe([]);
    });

    it('collapses block changes for the same pair into one queued delivery', function () {
        $local = bsyncLocalProfile();
        $alice = bsyncRemoteProfile('joinloops.org', 'alice');
        bsyncSeedHosts(['joinloops.org']);

        $filter = UserFilter::create([
            'user_id' => $local->id,
            'filterable_id' => $alice->id,
            'filterable_type' => Profile::class,
            'filter_type' => 'block',
        ]);

        Queue::assertPushed(DeliverBlockActivity::class, 1);

        $filter->delete();

        Queue::assertPushed(DeliverBlockActivity::class, 1);
    });

    it('never counts blocks received from other servers as disclosed', function () {
        $local = bsyncLocalProfile();
        bsyncBlock(bsyncRemoteProfile('joinloops.org', 'alice'), $local);

        expect(BlockSyncService::outboundDigests())->toBe([]);
    });
});

describe('endpoint', function () {
    it('rejects unsigned requests', function () {
        $this->get('/f/block_sync', ['Accept' => 'application/activity+json'])->assertStatus(401);
    });

    it('only lists blocks of actors hosted by the instance that signed the request', function () {
        $local = bsyncLocalProfile();
        [$private, $public] = bsyncKeyPair();

        bsyncRemoteProfile('joinloops.org', 'actor', [
            'remote_url' => 'https://joinloops.org/actor',
            'key_id' => 'https://joinloops.org/actor#main-key',
            'public_key' => $public,
        ]);

        $alice = bsyncRemoteProfile('joinloops.org', 'alice');
        $carol = bsyncRemoteProfile('remote2.example', 'carol');
        bsyncBlock($local, $alice);
        bsyncBlock($local, $carol);
        bsyncSeedHosts(['joinloops.org']);

        $response = $this->get('/f/block_sync', bsyncSignedGetHeaders($private, 'https://joinloops.org/actor#main-key', '/f/block_sync'))
            ->assertOk()
            ->assertJsonPath('type', 'OrderedCollection')
            ->assertJsonPath('totalItems', 1);

        $digest = BlockSyncService::digest([bsyncPair($local, $alice)]);

        expect($response->json('orderedItems'))->toBe([['type' => 'Block'] + bsyncPair($local, $alice)]);
        expect($response->json('blockSynchronizationDigest'))->toBe($digest);
        expect($response->headers->get('ETag'))->toBe('"'.$digest.'"');
    });

    it('refuses limited peers instead of answering with an empty set', function () {
        [$private, $public] = bsyncKeyPair();

        bsyncRemoteProfile('joinloops.org', 'actor', [
            'remote_url' => 'https://joinloops.org/actor',
            'key_id' => 'https://joinloops.org/actor#main-key',
            'public_key' => $public,
        ]);

        bsyncSeedHosts(['joinloops.org']);
        Cache::put('instances:unlisted:domains', ['joinloops.org'], 1209600);

        $this->get('/f/block_sync', bsyncSignedGetHeaders($private, 'https://joinloops.org/actor#main-key', '/f/block_sync'))
            ->assertForbidden();
    });
});

describe('inbox', function () {
    it('applies a Block activity and drops follows in both directions', function () {
        $local = bsyncLocalProfile();
        $alice = bsyncRemoteProfile('joinloops.org', 'alice');
        bsyncSeedHosts(['joinloops.org']);

        DB::table('followers')->insert([
            ['profile_id' => $local->id, 'following_id' => $alice->id, 'created_at' => now(), 'updated_at' => now()],
            ['profile_id' => $alice->id, 'following_id' => $local->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $headers = bsyncInboundHeaders($alice, 'https://joinloops.org/f/block_sync', BlockSyncService::digest([]));

        (new Inbox($headers, $local, [
            'id' => 'https://joinloops.org/blocks/1',
            'type' => 'Block',
            'actor' => $alice->remote_url,
            'object' => $local->permalink(),
        ]))->handle();

        expect(bsyncBlocks($alice, $local))->toBeTrue();
        expect(Follower::whereProfileId($local->id)->whereFollowingId($alice->id)->exists())->toBeFalse();
        expect(Follower::whereProfileId($alice->id)->whereFollowingId($local->id)->exists())->toBeFalse();
    });

    it('removes the block on Undo Block', function () {
        $local = bsyncLocalProfile();
        $alice = bsyncRemoteProfile('joinloops.org', 'alice');
        bsyncBlock($alice, $local);
        bsyncSeedHosts(['joinloops.org']);

        $headers = bsyncInboundHeaders($alice, 'https://joinloops.org/f/block_sync', BlockSyncService::digest([]));

        (new Inbox($headers, $local, [
            'id' => 'https://joinloops.org/blocks/1/undo',
            'type' => 'Undo',
            'actor' => $alice->remote_url,
            'object' => [
                'id' => 'https://joinloops.org/blocks/1',
                'type' => 'Block',
                'actor' => $alice->remote_url,
                'object' => $local->permalink(),
            ],
        ]))->handle();

        expect(bsyncBlocks($alice, $local))->toBeFalse();
    });

    it('ignores a Block signed from another server', function () {
        $local = bsyncLocalProfile();
        $alice = bsyncRemoteProfile('joinloops.org', 'alice');
        $mallory = bsyncRemoteProfile('evil.example', 'mallory');
        bsyncSeedHosts(['joinloops.org', 'evil.example']);

        $headers = bsyncInboundHeaders($mallory, 'https://evil.example/f/block_sync', BlockSyncService::digest([]));

        (new Inbox($headers, $local, [
            'id' => 'https://joinloops.org/blocks/1',
            'type' => 'Block',
            'actor' => $alice->remote_url,
            'object' => $local->permalink(),
        ]))->handle();

        expect(bsyncBlocks($alice, $local))->toBeFalse();
    });
});

describe('inbound header', function () {
    it('queues a synchronization when the digests differ and remembers the endpoint', function () {
        $local = bsyncLocalProfile();
        $alice = bsyncRemoteProfile('joinloops.org', 'alice');
        Instance::updateOrCreate(['domain' => 'joinloops.org']);
        bsyncSeedHosts(['joinloops.org']);

        BlockSyncService::handleInboundHeaders(bsyncInboundHeaders(
            $alice,
            'https://joinloops.org/f/block_sync',
            BlockSyncService::digest([bsyncPair($alice, $local)])
        ));

        Queue::assertPushed(BlockSyncPipeline::class, 1);
        expect(Instance::whereDomain('joinloops.org')->value('block_sync_url'))->toBe('https://joinloops.org/f/block_sync');
    });

    it('stays quiet when the digests agree', function () {
        $local = bsyncLocalProfile();
        $alice = bsyncRemoteProfile('joinloops.org', 'alice');
        bsyncBlock($alice, $local);
        bsyncSeedHosts(['joinloops.org']);

        BlockSyncService::handleInboundHeaders(bsyncInboundHeaders(
            $alice,
            'https://joinloops.org/f/block_sync',
            BlockSyncService::digest([bsyncPair($alice, $local)])
        ));

        Queue::assertNotPushed(BlockSyncPipeline::class);
    });

    it('ignores unsigned headers and endpoints on another server', function () {
        $alice = bsyncRemoteProfile('joinloops.org', 'alice');
        bsyncSeedHosts(['joinloops.org']);

        BlockSyncService::handleInboundHeaders(bsyncInboundHeaders($alice, 'https://joinloops.org/f/block_sync', str_repeat('1', 64), false));
        BlockSyncService::handleInboundHeaders(bsyncInboundHeaders($alice, 'https://victim.example/f/block_sync', str_repeat('1', 64)));

        Queue::assertNotPushed(BlockSyncPipeline::class);
    });
});

describe('synchronization', function () {
    it('adds blocks the authoritative server lists', function () {
        $local = bsyncLocalProfile();
        $alice = bsyncRemoteProfile('joinloops.org', 'alice');
        $url = 'https://joinloops.org/f/block_sync';
        bsyncSeedHosts(['joinloops.org']);
        bsyncFakeCollection($url, [bsyncPair($alice, $local)]);

        $result = BlockSyncService::synchronize('https://joinloops.org', $url, BlockSyncService::digest([bsyncPair($alice, $local)]));

        expect($result['added'])->toBe(1);
        expect(bsyncBlocks($alice, $local))->toBeTrue();
    });

    it('removes stale blocks when the collection hashes to its own digest', function () {
        $kept = bsyncLocalProfile();
        $stale = bsyncLocalProfile();
        $alice = bsyncRemoteProfile('joinloops.org', 'alice');
        bsyncBlock($alice, $kept);
        bsyncBlock($alice, $stale);

        $url = 'https://joinloops.org/f/block_sync';
        bsyncSeedHosts(['joinloops.org']);
        bsyncFakeCollection($url, [bsyncPair($alice, $kept)]);

        $result = BlockSyncService::synchronize('https://joinloops.org', $url, BlockSyncService::digest([bsyncPair($alice, $kept)]));

        expect($result['status'])->toBe('synchronized');
        expect($result['removed'])->toBe(1);
        expect(bsyncBlocks($alice, $kept))->toBeTrue();
        expect(bsyncBlocks($alice, $stale))->toBeFalse();
    });

    it('removes nothing when the collection changed while it was fetched', function () {
        $local = bsyncLocalProfile();
        $alice = bsyncRemoteProfile('joinloops.org', 'alice');
        bsyncBlock($alice, $local);

        $url = 'https://joinloops.org/f/block_sync';
        bsyncSeedHosts(['joinloops.org']);

        bsyncFakeCollection($url, [], BlockSyncService::digest([bsyncPair($alice, $local)]));

        $result = BlockSyncService::synchronize('https://joinloops.org', $url, str_repeat('1', 64));

        expect($result['status'])->toBe('synchronized_without_removals');
        expect(bsyncBlocks($alice, $local))->toBeTrue();
    });

    it('checks removals against the collection, not the header digest', function () {
        $local = bsyncLocalProfile();
        $alice = bsyncRemoteProfile('joinloops.org', 'alice');
        bsyncBlock($alice, $local);

        $url = 'https://joinloops.org/f/block_sync';
        bsyncSeedHosts(['joinloops.org']);
        bsyncFakeCollection($url, []);

        $result = BlockSyncService::synchronize('https://joinloops.org', $url, str_repeat('1', 64));

        expect($result['status'])->toBe('synchronized');
        expect(bsyncBlocks($alice, $local))->toBeFalse();
    });

    it('still adds blocks from a collection it cannot verify', function () {
        $local = bsyncLocalProfile();
        $alice = bsyncRemoteProfile('joinloops.org', 'alice');

        $url = 'https://joinloops.org/f/block_sync';
        bsyncSeedHosts(['joinloops.org']);
        bsyncFakeCollection($url, [bsyncPair($alice, $local)], str_repeat('1', 64));

        $result = BlockSyncService::synchronize('https://joinloops.org', $url);

        expect($result['status'])->toBe('synchronized_without_removals');
        expect($result['added'])->toBe(1);
        expect(bsyncBlocks($alice, $local))->toBeTrue();
    });

    it('keeps a block that is younger than the grace period', function () {
        $local = bsyncLocalProfile();
        $alice = bsyncRemoteProfile('joinloops.org', 'alice');
        bsyncBlock($alice, $local, 1);

        $url = 'https://joinloops.org/f/block_sync';
        bsyncSeedHosts(['joinloops.org']);
        bsyncFakeCollection($url, []);

        $result = BlockSyncService::synchronize('https://joinloops.org', $url, BlockSyncService::digest([]));

        expect($result['removed'])->toBe(0);
        expect(bsyncBlocks($alice, $local))->toBeTrue();
    });

    it('never treats a failed fetch as an empty collection', function () {
        $local = bsyncLocalProfile();
        $alice = bsyncRemoteProfile('joinloops.org', 'alice');
        bsyncBlock($alice, $local);

        [$private] = bsyncKeyPair();
        Cache::forever(InstanceActor::PKI_PRIVATE, $private);
        bsyncSeedHosts(['joinloops.org']);
        Http::fake(['*' => Http::response('', 500)]);

        $result = BlockSyncService::synchronize('https://joinloops.org', 'https://joinloops.org/f/block_sync', BlockSyncService::digest([]));

        expect($result['status'])->toBe('fetch_failed');
        expect(bsyncBlocks($alice, $local))->toBeTrue();
    });

    it('only removes during periodic reconciliation when the collection carries a digest', function () {
        $local = bsyncLocalProfile();
        $alice = bsyncRemoteProfile('joinloops.org', 'alice');
        bsyncBlock($alice, $local);

        $url = 'https://joinloops.org/f/block_sync';
        bsyncSeedHosts(['joinloops.org']);

        bsyncFakeCollection($url, [], false);
        expect(BlockSyncService::synchronize('https://joinloops.org', $url)['removed'])->toBe(0);
        expect(bsyncBlocks($alice, $local))->toBeTrue();

        $url = 'https://joinloops.org/f/block_sync_complete';
        bsyncFakeCollection($url, []);
        expect(BlockSyncService::synchronize('https://joinloops.org', $url)['removed'])->toBe(1);
        expect(bsyncBlocks($alice, $local))->toBeFalse();
    });
});
