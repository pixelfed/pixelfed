<?php

use App\Jobs\AvatarPipeline\RemoteAvatarFetch;
use App\Models\Profile;
use App\Util\ActivityPub\Helpers;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    Bus::fake();
});

/*
|--------------------------------------------------------------------------
| Returning actors after a remote profile delete
|--------------------------------------------------------------------------
|
| DeleteRemoteProfilePipeline soft-deletes the remote profile, which keeps its
| remote_url and key_id in the table. When the same actor shows up again (a
| Bridgy Fed user re-enables bridging, often under a new Bluesky handle), the
| tombstone must be restored instead of inserting a second row that violates
| the unique profiles.key_id index. See pixelfed/pixelfed#7888.
|
*/

/**
 * Seed the DNS and banned-domain caches so URL validation passes without a
 * real network lookup. Must run after the factories, the lazy refresh flushes
 * the array cache.
 */
function rdrpSeedHosts(array $hosts): void
{
    $hosts[] = config('pixelfed.domain.app');

    foreach ($hosts as $host) {
        Cache::put(
            'helpers:url:public-ips:v2:'.hash('xxh128', $host),
            ['state' => Helpers::URL_OK, 'ips' => ['203.0.113.40']],
            3600
        );
    }

    Cache::put('instances:banned:domains', [], 1209600);
}

/**
 * A minimal, valid remote actor document on bridge.example.
 */
function rdrpActorDocument(string $actorId, string $preferredUsername): array
{
    return [
        '@context' => ['https://www.w3.org/ns/activitystreams', 'https://w3id.org/security/v1'],
        'id' => $actorId,
        'type' => 'Person',
        'inbox' => $actorId.'/inbox',
        'outbox' => $actorId.'/outbox',
        'preferredUsername' => $preferredUsername,
        'name' => ucfirst($preferredUsername),
        'summary' => '',
        'url' => $actorId,
        'manuallyApprovesFollowers' => false,
        'publicKey' => [
            'id' => $actorId.'#key',
            'owner' => $actorId,
            'publicKeyPem' => "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqh\n-----END PUBLIC KEY-----\n",
        ],
        'endpoints' => ['sharedInbox' => 'https://bridge.example/inbox'],
    ];
}

function rdrpFakeActor(string $actorId, string $preferredUsername): void
{
    Http::fake([
        'bridge.example/.well-known/webfinger*' => Http::response(
            ['subject' => "acct:{$preferredUsername}@bridge.example"],
            200,
            ['Content-Type' => 'application/jrd+json']
        ),
        'bridge.example/ap/*' => Http::response(
            json_encode(rdrpActorDocument($actorId, $preferredUsername)),
            200,
            ['Content-Type' => 'application/activity+json']
        ),
    ]);
}

describe('restoring a soft-deleted remote profile', function () {
    it('restores the tombstone instead of inserting a duplicate when the actor returns', function () {
        $actorId = 'https://bridge.example/ap/did:plc:abc123';

        $deleted = Profile::factory()->create([
            'user_id' => null,
            'domain' => 'bridge.example',
            'username' => '@old.handle.example@bridge.example',
            'remote_url' => $actorId,
            'key_id' => $actorId.'#key',
            'status' => 'delete',
            'last_fetched_at' => now()->subDay(),
            'deleted_at' => now()->subDay(),
        ]);

        rdrpSeedHosts(['bridge.example']);

        // Same actor id and key id, but the Bluesky handle changed in between,
        // so neither remote_url nor (domain, username) can be matched by the
        // default soft-delete scope.
        rdrpFakeActor($actorId, 'new.handle.example');

        $result = Helpers::profileUpdateOrCreate($actorId);

        expect($result)->not->toBeNull()
            ->and($result->id)->toBe($deleted->id)
            ->and($result->trashed())->toBeFalse()
            ->and($result->status)->toBeNull()
            ->and($result->username)->toBe('@new.handle.example@bridge.example')
            ->and($result->remote_url)->toBe($actorId)
            ->and(Profile::withTrashed()->where('key_id', $actorId.'#key')->count())->toBe(1);

        // The pipeline force-deleted the avatar row, so the restore must
        // trigger a fresh avatar fetch despite the recent last_fetched_at.
        Bus::assertDispatched(RemoteAvatarFetch::class);
    });

    it('matches a tombstone on key_id when the actor id changed', function () {
        $oldActorId = 'https://bridge.example/ap/old-id';
        $newActorId = 'https://bridge.example/ap/did:plc:abc123';

        $deleted = Profile::factory()->create([
            'user_id' => null,
            'domain' => 'bridge.example',
            'username' => '@someone@bridge.example',
            'remote_url' => $oldActorId,
            'key_id' => $newActorId.'#key',
            'status' => 'delete',
            'deleted_at' => now()->subDay(),
        ]);

        rdrpSeedHosts(['bridge.example']);
        rdrpFakeActor($newActorId, 'renamed');

        $result = Helpers::profileUpdateOrCreate($newActorId);

        expect($result)->not->toBeNull()
            ->and($result->id)->toBe($deleted->id)
            ->and($result->trashed())->toBeFalse()
            ->and($result->remote_url)->toBe($newActorId)
            ->and(Profile::withTrashed()->where('key_id', $newActorId.'#key')->count())->toBe(1);
    });

    it('does not adopt a live profile that holds the same key_id under another actor id', function () {
        $otherActorId = 'https://bridge.example/ap/other';
        $newActorId = 'https://bridge.example/ap/did:plc:abc123';

        $live = Profile::factory()->create([
            'user_id' => null,
            'domain' => 'bridge.example',
            'username' => '@other@bridge.example',
            'remote_url' => $otherActorId,
            'key_id' => $newActorId.'#key',
        ]);

        rdrpSeedHosts(['bridge.example']);
        rdrpFakeActor($newActorId, 'newcomer');

        // Without a tombstone to restore, the unique key_id index still
        // rejects the insert; the live row must be left untouched.
        expect(fn () => Helpers::profileUpdateOrCreate($newActorId))
            ->toThrow(\Illuminate\Database\QueryException::class)
            ->and($live->fresh()->remote_url)->toBe($otherActorId);
    });

    it('keeps the existing username when a tombstone still holds the wanted handle', function () {
        $deletedActorId = 'https://bridge.example/ap/gone';
        $liveActorId = 'https://bridge.example/ap/alive';

        Profile::factory()->create([
            'user_id' => null,
            'domain' => 'bridge.example',
            'username' => '@wanted@bridge.example',
            'remote_url' => $deletedActorId,
            'key_id' => $deletedActorId.'#key',
            'deleted_at' => now()->subDay(),
        ]);

        $live = Profile::factory()->create([
            'user_id' => null,
            'domain' => 'bridge.example',
            'username' => '@alive@bridge.example',
            'remote_url' => $liveActorId,
            'key_id' => $liveActorId.'#key',
        ]);

        rdrpSeedHosts(['bridge.example']);
        rdrpFakeActor($liveActorId, 'wanted');

        $result = Helpers::profileUpdateOrCreate($liveActorId);

        expect($result)->not->toBeNull()
            ->and($result->id)->toBe($live->id)
            ->and($live->fresh()->username)->toBe('@alive@bridge.example');
    });
});
