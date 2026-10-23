<?php

use App\Jobs\FollowPipeline\FollowersSyncPipeline;
use App\Jobs\FollowPipeline\UnfollowPipeline;
use App\Jobs\ProfilePipeline\SigningActorDiscoveryPipeline;
use App\Models\Follower;
use App\Models\FollowRequest;
use App\Models\InstanceActor;
use App\Models\Profile;
use App\Models\User;
use App\Services\ActivityPubDeliveryService;
use App\Services\FollowersSyncService;
use App\Services\RelationshipService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| FEP-8fcf: Followers collection synchronization across servers
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    Redis::spy();
    Queue::fake();

    config([
        'instance.enable_cc' => false,
        'federation.activitypub.enabled' => true,
        'federation.activitypub.remoteFollow' => true,
        'federation.activitypub.followers_sync.enabled' => true,
    ]);
});

function fsyncLocalProfile(): Profile
{
    $user = User::factory()->create();
    $user->refresh();

    return $user->profile;
}

function fsyncRemoteProfile(string $domain, string $username, array $attributes = []): Profile
{
    $actor = "https://{$domain}/users/{$username}";

    return Profile::factory()->remote()->create(array_merge([
        'domain' => $domain,
        'username' => "@{$username}@{$domain}",
        'remote_url' => $actor,
        'inbox_url' => "{$actor}/inbox",
        'sharedInbox' => "https://{$domain}/inbox",
        'followers_url' => "{$actor}/followers",
    ], $attributes));
}

/**
 * Insert a follow without going through FollowerObserver, optionally aged so
 * it falls outside the removal grace period.
 */
function fsyncFollow(Profile $actor, Profile $target, int $ageInMinutes = 120): void
{
    DB::table('followers')->insert([
        'profile_id' => $actor->id,
        'following_id' => $target->id,
        'created_at' => now()->subMinutes($ageInMinutes),
        'updated_at' => now()->subMinutes($ageInMinutes),
    ]);
}

/**
 * Seed the DNS and banned-domain caches so URL validation passes without a
 * network lookup. Call after factories, the lazy refresh can flush the cache.
 */
function fsyncSeedHosts(array $hosts): void
{
    foreach ($hosts as $host) {
        Cache::put('helpers:url:public-ips:'.hash('xxh128', $host), ['203.0.113.40'], 3600);
    }

    Cache::put('instances:banned:domains', [], 1209600);
}

function fsyncKeyPair(): array
{
    $key = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);

    openssl_pkey_export($key, $private);

    return [$private, openssl_pkey_get_details($key)['key']];
}

function fsyncInProduction(callable $fn): mixed
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

function fsyncSignedGetHeaders(string $privateKey, string $keyId, string $path, string $host = 'pixelfed.test'): array
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

function fsyncInboundHeaders(Profile $sender, array $params, bool $signed = true): array
{
    $list = '(request-target) host date digest'.($signed ? ' collection-synchronization' : '');

    return [
        'signature' => [
            sprintf('keyId="%s",algorithm="rsa-sha256",headers="%s",signature="dGVzdA=="', $sender->key_id, $list),
        ],
        'collection-synchronization' => [
            sprintf('collectionId="%s", url="%s", digest="%s"', $params['collectionId'], $params['url'], $params['digest']),
        ],
    ];
}

function fsyncFakeCollection(string $url, array $items): void
{
    [$private] = fsyncKeyPair();
    Cache::forever(InstanceActor::PKI_PRIVATE, $private);

    Http::fake([
        $url => Http::response(json_encode([
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $url,
            'type' => 'OrderedCollection',
            'orderedItems' => $items,
        ]), 200, ['Content-Type' => 'application/activity+json']),
    ]);
}

describe('sender', function () {
    it('signs a Collection-Synchronization header scoped to each destination', function () {
        Http::fake();

        $profile = fsyncLocalProfile();

        $alice = fsyncRemoteProfile('joinloops.org', 'alice');
        $bob = fsyncRemoteProfile('joinloops.org', 'bob');
        $carol = fsyncRemoteProfile('remote2.example', 'carol');

        foreach ([$alice, $bob, $carol] as $follower) {
            fsyncFollow($follower, $profile);
        }

        fsyncSeedHosts(['joinloops.org', 'remote2.example', 'remote3.example']);

        fsyncInProduction(fn () => ActivityPubDeliveryService::pool(
            $profile,
            [
                'https://joinloops.org/inbox',
                'https://remote2.example/inbox',
                'https://remote3.example/inbox',
            ],
            ['id' => $profile->permalink('#create'), 'type' => 'Create', 'actor' => $profile->permalink()],
            null,
            true
        ));

        $expected = [
            'https://joinloops.org/inbox' => FollowersSyncService::digest([$alice->remote_url, $bob->remote_url]),
            'https://remote2.example/inbox' => FollowersSyncService::digest([$carol->remote_url]),
            'https://remote3.example/inbox' => FollowersSyncService::EMPTY_DIGEST,
        ];

        Http::assertSentCount(3);

        foreach (Http::recorded() as [$request]) {
            $params = FollowersSyncService::parseHeader($request->header('Collection-Synchronization')[0] ?? null);

            // Read the signed header list straight from the Signature header:
            // HttpSignature::parseSignatureHeader() also DNS-validates the
            // keyId, which is this instance and is not seeded in the cache.
            preg_match('/headers="([^"]*)"/', $request->header('Signature')[0] ?? '', $signed);

            expect($params)->not->toBeNull();
            expect($params['collectionId'])->toBe($profile->permalink('/followers'));
            expect($params['url'])->toBe($profile->permalink('/followers_synchronization'));
            expect($params['digest'])->toBe($expected[$request->url()]);
            expect(explode(' ', $signed[1] ?? ''))->toContain('collection-synchronization');
        }
    });

    it('does not send the header unless asked to', function () {
        Http::fake();

        $profile = fsyncLocalProfile();
        fsyncFollow(fsyncRemoteProfile('joinloops.org', 'alice'), $profile);
        fsyncSeedHosts(['joinloops.org']);

        fsyncInProduction(fn () => ActivityPubDeliveryService::pool(
            $profile,
            ['https://joinloops.org/inbox'],
            ['id' => $profile->permalink('#create'), 'type' => 'Create', 'actor' => $profile->permalink()]
        ));

        Http::assertSent(fn ($request) => empty($request->header('Collection-Synchronization')));
    });

    it('drops the cached digests when a relationship changes', function () {
        $profile = fsyncLocalProfile();
        $alice = fsyncRemoteProfile('joinloops.org', 'alice');

        expect(FollowersSyncService::outboundDigests($profile))->toBe([]);

        fsyncFollow($alice, $profile);
        RelationshipService::forget($alice->id, $profile->id);

        expect(FollowersSyncService::outboundDigests($profile))->toBe([
            'https://joinloops.org' => FollowersSyncService::digest([$alice->remote_url]),
        ]);
    });
});

describe('partial followers endpoint', function () {
    it('rejects unsigned requests', function () {
        $profile = fsyncLocalProfile();

        $this->get("/users/{$profile->username}/followers_synchronization", ['Accept' => 'application/activity+json'])
            ->assertStatus(401);
    });

    it('queues discovery of an unknown signer instead of fetching it inline', function () {
        Http::fake();

        $profile = fsyncLocalProfile();
        [$private] = fsyncKeyPair();
        fsyncSeedHosts(['joinloops.org']);

        $path = "/users/{$profile->username}/followers_synchronization";

        $this->get($path, fsyncSignedGetHeaders($private, 'https://joinloops.org/actor#main-key', $path))
            ->assertStatus(401);

        Http::assertNothingSent();
        Queue::assertPushed(SigningActorDiscoveryPipeline::class);
    });

    it('only lists the followers hosted by the instance that signed the request', function () {
        $profile = fsyncLocalProfile();
        [$private, $public] = fsyncKeyPair();

        fsyncRemoteProfile('joinloops.org', 'actor', [
            'remote_url' => 'https://joinloops.org/actor',
            'key_id' => 'https://joinloops.org/actor#main-key',
            'public_key' => $public,
        ]);

        $alice = fsyncRemoteProfile('joinloops.org', 'alice');
        $bob = fsyncRemoteProfile('joinloops.org', 'bob');
        $carol = fsyncRemoteProfile('remote2.example', 'carol');

        foreach ([$alice, $bob, $carol] as $follower) {
            fsyncFollow($follower, $profile);
        }

        fsyncSeedHosts(['joinloops.org']);

        $path = "/users/{$profile->username}/followers_synchronization";

        $response = $this->get($path, fsyncSignedGetHeaders($private, 'https://joinloops.org/actor#main-key', $path))
            ->assertOk()
            ->assertJsonPath('type', 'OrderedCollection')
            ->assertJsonPath('id', $profile->permalink('/followers_synchronization'));

        expect($response->json('orderedItems'))->toBe([$alice->remote_url, $bob->remote_url]);
    });

    it('rejects a signature made for another path', function () {
        $profile = fsyncLocalProfile();
        [$private, $public] = fsyncKeyPair();

        fsyncRemoteProfile('joinloops.org', 'actor', [
            'remote_url' => 'https://joinloops.org/actor',
            'key_id' => 'https://joinloops.org/actor#main-key',
            'public_key' => $public,
        ]);

        fsyncSeedHosts(['joinloops.org']);

        $headers = fsyncSignedGetHeaders($private, 'https://joinloops.org/actor#main-key', "/users/{$profile->username}/followers");

        $this->get("/users/{$profile->username}/followers_synchronization", $headers)
            ->assertStatus(401);
    });
});

describe('inbound header', function () {
    it('queues a synchronization when the digests differ', function () {
        $local = fsyncLocalProfile();
        $sender = fsyncRemoteProfile('joinloops.org', 'alice', ['key_id' => 'https://joinloops.org/users/alice#main-key']);
        fsyncFollow($local, $sender);
        fsyncSeedHosts(['joinloops.org']);

        FollowersSyncService::handleInboundHeaders(fsyncInboundHeaders($sender, [
            'collectionId' => $sender->followers_url,
            'url' => $sender->remote_url.'/followers_synchronization',
            'digest' => FollowersSyncService::EMPTY_DIGEST,
        ]));

        Queue::assertPushed(FollowersSyncPipeline::class, 1);
    });

    it('stays quiet when the digests agree', function () {
        $local = fsyncLocalProfile();
        $sender = fsyncRemoteProfile('joinloops.org', 'alice', ['key_id' => 'https://joinloops.org/users/alice#main-key']);
        fsyncFollow($local, $sender);
        fsyncSeedHosts(['joinloops.org']);

        FollowersSyncService::handleInboundHeaders(fsyncInboundHeaders($sender, [
            'collectionId' => $sender->followers_url,
            'url' => $sender->remote_url.'/followers_synchronization',
            'digest' => FollowersSyncService::digest([$local->permalink()]),
        ]));

        Queue::assertNotPushed(FollowersSyncPipeline::class);
    });

    it('ignores a header that is not covered by the signature', function () {
        $sender = fsyncRemoteProfile('joinloops.org', 'alice', ['key_id' => 'https://joinloops.org/users/alice#main-key']);
        fsyncFollow(fsyncLocalProfile(), $sender);
        fsyncSeedHosts(['joinloops.org']);

        FollowersSyncService::handleInboundHeaders(fsyncInboundHeaders($sender, [
            'collectionId' => $sender->followers_url,
            'url' => $sender->remote_url.'/followers_synchronization',
            'digest' => FollowersSyncService::EMPTY_DIGEST,
        ], false));

        Queue::assertNotPushed(FollowersSyncPipeline::class);
    });

    it('ignores a collection or url that does not belong to the sender', function () {
        $sender = fsyncRemoteProfile('joinloops.org', 'alice', ['key_id' => 'https://joinloops.org/users/alice#main-key']);
        fsyncFollow(fsyncLocalProfile(), $sender);
        fsyncSeedHosts(['joinloops.org']);

        FollowersSyncService::handleInboundHeaders(fsyncInboundHeaders($sender, [
            'collectionId' => 'https://joinloops.org/users/mallory/followers',
            'url' => $sender->remote_url.'/followers_synchronization',
            'digest' => FollowersSyncService::EMPTY_DIGEST,
        ]));

        FollowersSyncService::handleInboundHeaders(fsyncInboundHeaders($sender, [
            'collectionId' => $sender->followers_url,
            'url' => 'https://victim.example/users/bob/followers_synchronization',
            'digest' => FollowersSyncService::EMPTY_DIGEST,
        ]));

        Queue::assertNotPushed(FollowersSyncPipeline::class);
    });

    it('synchronizes the same actor once per cooldown window', function () {
        $sender = fsyncRemoteProfile('joinloops.org', 'alice', ['key_id' => 'https://joinloops.org/users/alice#main-key']);
        fsyncFollow(fsyncLocalProfile(), $sender);
        fsyncSeedHosts(['joinloops.org']);

        $headers = fsyncInboundHeaders($sender, [
            'collectionId' => $sender->followers_url,
            'url' => $sender->remote_url.'/followers_synchronization',
            'digest' => FollowersSyncService::EMPTY_DIGEST,
        ]);

        FollowersSyncService::handleInboundHeaders($headers);
        FollowersSyncService::handleInboundHeaders($headers);

        Queue::assertPushed(FollowersSyncPipeline::class, 1);
    });
});

describe('synchronization', function () {
    it('removes local followers the authoritative server does not list', function () {
        $kept = fsyncLocalProfile();
        $stale = fsyncLocalProfile();
        $sender = fsyncRemoteProfile('joinloops.org', 'alice');
        fsyncFollow($kept, $sender);
        fsyncFollow($stale, $sender);

        $url = $sender->remote_url.'/followers_synchronization';
        fsyncSeedHosts(['joinloops.org']);
        fsyncFakeCollection($url, [$kept->permalink()]);

        $result = FollowersSyncService::synchronize(
            $sender,
            $sender->followers_url,
            $url,
            FollowersSyncService::digest([$kept->permalink()])
        );

        expect($result['status'])->toBe('synchronized');
        expect($result['removed'])->toBe(1);
        expect(Follower::whereProfileId($stale->id)->whereFollowingId($sender->id)->exists())->toBeFalse();
        expect(Follower::whereProfileId($kept->id)->whereFollowingId($sender->id)->exists())->toBeTrue();
        Queue::assertPushed(UnfollowPipeline::class, 1);
    });

    it('removes nothing when the list does not hash to the signed digest', function () {
        $kept = fsyncLocalProfile();
        $stale = fsyncLocalProfile();
        $sender = fsyncRemoteProfile('joinloops.org', 'alice');
        fsyncFollow($kept, $sender);
        fsyncFollow($stale, $sender);

        $url = $sender->remote_url.'/followers_synchronization';
        fsyncSeedHosts(['joinloops.org']);
        fsyncFakeCollection($url, [$kept->permalink()]);

        $result = FollowersSyncService::synchronize(
            $sender,
            $sender->followers_url,
            $url,
            FollowersSyncService::digest(['https://pixelfed.test/users/somebody-else'])
        );

        expect($result['status'])->toBe('synchronized_without_removals');
        expect(Follower::whereProfileId($stale->id)->whereFollowingId($sender->id)->exists())->toBeTrue();
    });

    it('never treats a failed fetch as an empty collection', function () {
        $local = fsyncLocalProfile();
        $sender = fsyncRemoteProfile('joinloops.org', 'alice');
        fsyncFollow($local, $sender);

        [$private] = fsyncKeyPair();
        Cache::forever(InstanceActor::PKI_PRIVATE, $private);
        fsyncSeedHosts(['joinloops.org']);
        Http::fake(['*' => Http::response('', 500)]);

        $result = FollowersSyncService::synchronize(
            $sender,
            $sender->followers_url,
            $sender->remote_url.'/followers_synchronization',
            FollowersSyncService::EMPTY_DIGEST
        );

        expect($result['status'])->toBe('fetch_failed');
        expect(Follower::whereProfileId($local->id)->whereFollowingId($sender->id)->exists())->toBeTrue();
    });

    it('keeps a follow that is younger than the grace period', function () {
        $local = fsyncLocalProfile();
        $sender = fsyncRemoteProfile('joinloops.org', 'alice');
        fsyncFollow($local, $sender, 1);

        $url = $sender->remote_url.'/followers_synchronization';
        fsyncSeedHosts(['joinloops.org']);
        fsyncFakeCollection($url, []);

        $result = FollowersSyncService::synchronize($sender, $sender->followers_url, $url, FollowersSyncService::EMPTY_DIGEST);

        expect($result['removed'])->toBe(0);
        expect(Follower::whereProfileId($local->id)->whereFollowingId($sender->id)->exists())->toBeTrue();
    });

    it('keeps a follower that is listed under its id based actor url', function () {
        $local = fsyncLocalProfile();
        $sender = fsyncRemoteProfile('joinloops.org', 'alice');
        fsyncFollow($local, $sender);

        $listedAs = url('users/'.$local->id);
        $url = $sender->remote_url.'/followers_synchronization';
        fsyncSeedHosts(['joinloops.org']);
        fsyncFakeCollection($url, [$listedAs]);

        $result = FollowersSyncService::synchronize($sender, $sender->followers_url, $url, FollowersSyncService::digest([$listedAs]));

        expect($result['status'])->toBe('synchronized');
        expect($result['removed'])->toBe(0);
        expect(Follower::whereProfileId($local->id)->whereFollowingId($sender->id)->exists())->toBeTrue();
    });

    it('removes nothing when the list holds a local url it cannot interpret', function () {
        $local = fsyncLocalProfile();
        $sender = fsyncRemoteProfile('joinloops.org', 'alice');
        fsyncFollow($local, $sender);

        $listedAs = url('@'.$local->username);
        $url = $sender->remote_url.'/followers_synchronization';
        fsyncSeedHosts(['joinloops.org']);
        fsyncFakeCollection($url, [$listedAs]);

        $result = FollowersSyncService::synchronize($sender, $sender->followers_url, $url, FollowersSyncService::digest([$listedAs]));

        expect($result['status'])->toBe('synchronized_without_removals');
        expect(Follower::whereProfileId($local->id)->whereFollowingId($sender->id)->exists())->toBeTrue();
    });

    it('accepts a pending follow request the authoritative server already lists', function () {
        $local = fsyncLocalProfile();
        $sender = fsyncRemoteProfile('joinloops.org', 'alice');

        FollowRequest::create([
            'follower_id' => $local->id,
            'following_id' => $sender->id,
        ]);

        $url = $sender->remote_url.'/followers_synchronization';
        fsyncSeedHosts(['joinloops.org']);
        fsyncFakeCollection($url, [$local->permalink()]);

        $result = FollowersSyncService::synchronize($sender, $sender->followers_url, $url, FollowersSyncService::digest([$local->permalink()]));

        expect($result['accepted'])->toBe(1);
        expect(Follower::whereProfileId($local->id)->whereFollowingId($sender->id)->exists())->toBeTrue();
        expect(FollowRequest::whereFollowerId($local->id)->whereFollowingId($sender->id)->exists())->toBeFalse();
    });

    it('undoes a follow the authoritative server lists but we do not know', function () {
        $local = fsyncLocalProfile();
        $sender = fsyncRemoteProfile('joinloops.org', 'alice');

        $url = $sender->remote_url.'/followers_synchronization';
        fsyncSeedHosts(['joinloops.org']);
        fsyncFakeCollection($url, [$local->permalink()]);

        $result = FollowersSyncService::synchronize($sender, $sender->followers_url, $url, FollowersSyncService::digest([$local->permalink()]));

        expect($result['undone'])->toBe(1);
        expect(Follower::whereProfileId($local->id)->whereFollowingId($sender->id)->exists())->toBeFalse();
    });

    it('does not fetch anything for a collection that is not the sender\'s followers collection', function () {
        Http::fake();

        $local = fsyncLocalProfile();
        $sender = fsyncRemoteProfile('joinloops.org', 'alice');
        fsyncFollow($local, $sender);
        fsyncSeedHosts(['joinloops.org']);

        $result = FollowersSyncService::synchronize(
            $sender,
            'https://joinloops.org/users/mallory/followers',
            $sender->remote_url.'/followers_synchronization',
            FollowersSyncService::EMPTY_DIGEST
        );

        expect($result['status'])->toBe('collection_mismatch');
        Http::assertNothingSent();
    });
});
