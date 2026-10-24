<?php

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
| Remote handle collisions on refresh
|--------------------------------------------------------------------------
|
| A remote profile is matched by its actor id (remote_url), not by its
| mutable handle. When a refresh would land on a webfinger handle that a
| different profile on the same domain already holds, the existing profile
| keeps its own username instead of stealing the handle or hitting the
| unique (domain, username) constraint.
|
*/

/**
 * Seed the DNS and banned-domain caches so URL validation passes without a
 * real network lookup. Must run after the factories, the lazy refresh flushes
 * the array cache.
 */
function rhcSeedHosts(array $hosts): void
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
 * A minimal, valid remote actor document for the given id on remote.example.
 */
function rhcActorDocument(string $actorId, string $preferredUsername): array
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
            'id' => $actorId.'#main-key',
            'owner' => $actorId,
            'publicKeyPem' => "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqh\n-----END PUBLIC KEY-----\n",
        ],
        'endpoints' => ['sharedInbox' => 'https://remote.example/inbox'],
    ];
}

describe('remote handle collision', function () {
    it('keeps the existing username when a refresh would collide with another profile on the same domain', function () {
        // P1 already holds the desired handle on remote.example.
        $p1 = Profile::factory()->create([
            'user_id' => null,
            'domain' => 'remote.example',
            'username' => '@taken@remote.example',
            'remote_url' => 'https://remote.example/users/p1',
        ]);

        // P2 is the profile being refreshed, currently under its own handle.
        $p2ActorId = 'https://remote.example/users/p2';
        $p2 = Profile::factory()->create([
            'user_id' => null,
            'domain' => 'remote.example',
            'username' => '@p2@remote.example',
            'remote_url' => $p2ActorId,
        ]);

        // Seed after the factories so the lazy refresh does not wipe it.
        rhcSeedHosts(['remote.example']);

        // The actor document claims preferredUsername "taken"; with no
        // cross-domain webfinger subject, verifiedHandle resolves the desired
        // handle to @taken@remote.example, colliding with P1.
        Http::fake([
            'remote.example/.well-known/webfinger*' => Http::response(
                ['subject' => 'acct:taken@remote.example'],
                200,
                ['Content-Type' => 'application/jrd+json']
            ),
            'remote.example/users/p2*' => Http::response(
                json_encode(rhcActorDocument($p2ActorId, 'taken')),
                200,
                ['Content-Type' => 'application/activity+json']
            ),
        ]);

        $result = Helpers::profileUpdateOrCreate($p2ActorId);

        expect($result)->not->toBeNull()
            ->and($result->id)->toBe($p2->id)
            ->and($p2->fresh()->username)->toBe('@p2@remote.example')
            ->and($p1->fresh()->username)->toBe('@taken@remote.example')
            ->and(Profile::where('domain', 'remote.example')->where('username', '@taken@remote.example')->count())->toBe(1);
    });
});
