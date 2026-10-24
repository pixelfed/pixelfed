<?php

use App\Models\InstanceActor;
use App\Models\Profile;
use App\Models\User;
use App\Services\FollowersSyncService;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| FEP-8fcf: the Undo{Follow} reconciliation loop is bounded by attempts
|--------------------------------------------------------------------------
|
| A remote server that lists more local profiles than MAX_UNDO_PER_RUN must
| never be able to drive more than that many outbound deliveries in a single
| run, no matter how each delivery fails.
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

function fsyncUndoBoundLocalProfile(): Profile
{
    $user = User::factory()->create();
    $user->refresh();

    return $user->profile;
}

function fsyncUndoBoundRemoteProfile(string $domain, string $username): Profile
{
    $actor = "https://{$domain}/users/{$username}";

    return Profile::factory()->remote()->create([
        'domain' => $domain,
        'username' => "@{$username}@{$domain}",
        'remote_url' => $actor,
        'inbox_url' => "{$actor}/inbox",
        'sharedInbox' => "https://{$domain}/inbox",
        'followers_url' => "{$actor}/followers",
    ]);
}

/**
 * Seed the DNS and banned-domain caches so URL validation passes without a
 * network lookup.
 */
function fsyncUndoBoundSeedHosts(array $hosts): void
{
    foreach ($hosts as $host) {
        Cache::put('helpers:url:public-ips:'.hash('xxh128', $host), ['203.0.113.40'], 3600);
    }

    Cache::put('instances:banned:domains', [], 1209600);
}

function fsyncUndoBoundKeyPair(): string
{
    $key = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);

    openssl_pkey_export($key, $private);

    return $private;
}

function fsyncUndoBoundInProduction(callable $fn): mixed
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

/**
 * One more than MAX_UNDO_PER_RUN local profiles, none of which follow the
 * sender, returned as actor ids in listing order.
 *
 * @return array<int, string>
 */
function fsyncUndoBoundItems(int $overflow = 5): array
{
    $items = [];

    for ($i = 0; $i < FollowersSyncService::MAX_UNDO_PER_RUN + $overflow; $i++) {
        $items[] = fsyncUndoBoundLocalProfile()->permalink();
    }

    return $items;
}

describe('undo delivery bound', function () {
    it('attempts at most MAX_UNDO_PER_RUN deliveries when every delivery fails', function () {
        $sender = fsyncUndoBoundRemoteProfile('joinloops.org', 'alice');
        $items = fsyncUndoBoundItems();

        $url = $sender->remote_url.'/followers_synchronization';
        fsyncUndoBoundSeedHosts(['joinloops.org']);
        Cache::forever(InstanceActor::PKI_PRIVATE, fsyncUndoBoundKeyPair());

        $attempts = 0;

        Http::fake([
            $url => Http::response(json_encode([
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id' => $url,
                'type' => 'OrderedCollection',
                'orderedItems' => $items,
            ]), 200, ['Content-Type' => 'application/activity+json']),
            'joinloops.org/inbox' => function (Request $request) use (&$attempts) {
                $attempts++;

                // A response-carrying transport failure (a truncated or
                // aborted body) surfaces as a RequestException, not a
                // ConnectionException, so the host is never marked unavailable
                // and the loop must be bounded by the attempt counter alone.
                throw new RequestException(new Response(new GuzzleResponse(500)));
            },
        ]);

        $result = fsyncUndoBoundInProduction(fn () => FollowersSyncService::synchronize(
            $sender,
            $sender->followers_url,
            $url,
            FollowersSyncService::digest($items)
        ));

        expect($attempts)->toBe(FollowersSyncService::MAX_UNDO_PER_RUN);
        expect($result['undone'])->toBe(FollowersSyncService::MAX_UNDO_PER_RUN);
    });

    it('stops at MAX_UNDO_PER_RUN deliveries even when every delivery succeeds', function () {
        $sender = fsyncUndoBoundRemoteProfile('joinloops.org', 'alice');
        $items = fsyncUndoBoundItems();

        $url = $sender->remote_url.'/followers_synchronization';
        fsyncUndoBoundSeedHosts(['joinloops.org']);
        Cache::forever(InstanceActor::PKI_PRIVATE, fsyncUndoBoundKeyPair());

        $attempts = 0;

        Http::fake([
            $url => Http::response(json_encode([
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id' => $url,
                'type' => 'OrderedCollection',
                'orderedItems' => $items,
            ]), 200, ['Content-Type' => 'application/activity+json']),
            'joinloops.org/inbox' => function (Request $request) use (&$attempts) {
                $attempts++;

                return Http::response('', 202);
            },
        ]);

        $result = fsyncUndoBoundInProduction(fn () => FollowersSyncService::synchronize(
            $sender,
            $sender->followers_url,
            $url,
            FollowersSyncService::digest($items)
        ));

        expect($attempts)->toBe(FollowersSyncService::MAX_UNDO_PER_RUN);
        expect($result['undone'])->toBe(FollowersSyncService::MAX_UNDO_PER_RUN);
    });
});
