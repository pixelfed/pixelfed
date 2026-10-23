<?php

use App\Jobs\InboxPipeline\ActivityHandler;
use App\Jobs\InboxPipeline\InboxValidator;
use App\Jobs\InboxPipeline\InboxWorker;
use App\Models\Profile;
use App\Models\User;
use App\Util\ActivityPub\Helpers;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Same-host actor spoofing
|--------------------------------------------------------------------------
|
| Two remote accounts can live on the same host, so they share a domain.
| Checking only the host of the signing key lets one of them sign an
| activity that claims to be from the other. The inbox has to bind the
| signing key to the exact actor the activity names, not just the host.
|
*/

beforeEach(function () {
    Queue::fake();

    config([
        'instance.enable_cc' => false,
        'federation.activitypub.enabled' => true,
    ]);
});

function shsLocalUser(): User
{
    $user = User::factory()->create();
    $user->refresh();

    return $user;
}

/**
 * Seed the DNS and banned-domain caches so URL validation passes without a
 * network lookup. Call after factories, the lazy refresh can flush the cache.
 */
function shsSeedHosts(array $hosts = ['remote.example']): void
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

function shsInProduction(callable $fn): mixed
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
 * A remote actor with a real key pair so requests can be signed end to end.
 */
function shsStranger(string $domain, string $username): array
{
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    $actor = "https://{$domain}/users/{$username}";

    return [
        'key' => $key,
        'actor' => $actor,
        'public' => openssl_pkey_get_details($key)['key'],
    ];
}

/**
 * Store a remote Profile row for the stranger with its own key_id and the
 * matching public key, so Profile::whereKeyId resolves the signer.
 */
function shsSeedProfile(array $stranger, string $domain, string $username): Profile
{
    return Profile::factory()->remote()->create([
        'domain' => $domain,
        'username' => "@{$username}@{$domain}",
        'remote_url' => $stranger['actor'],
        'inbox_url' => $stranger['actor'].'/inbox',
        'sharedInbox' => "https://{$domain}/inbox",
        'key_id' => $stranger['actor'].'#main-key',
        'public_key' => $stranger['public'],
        'last_fetched_at' => now(),
    ]);
}

/**
 * Sign the request with the signer's key, using the signer's keyId so the
 * inbox resolves the signer profile. The claimed actor comes from the body.
 */
function shsSignedHeaders(array $signer, string $path, string $body): array
{
    $host = config('pixelfed.domain.app');
    $date = now()->toRfc7231String();
    $digest = 'SHA-256='.base64_encode(hash('sha256', $body, true));

    openssl_sign(
        "(request-target): post {$path}\nhost: {$host}\ndate: {$date}\ndigest: {$digest}",
        $signature,
        $signer['key'],
        OPENSSL_ALGO_SHA256
    );

    return [
        'host' => [$host],
        'date' => [$date],
        'digest' => [$digest],
        'content-type' => ['application/activity+json'],
        'signature' => ['keyId="'.$signer['actor'].'#main-key",algorithm="rsa-sha256",headers="(request-target) host date digest",signature="'.base64_encode($signature).'"'],
    ];
}

function shsFollowBody(string $claimedActor, string $localActor): string
{
    return json_encode([
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => $claimedActor.'/follows/'.uniqid(),
        'type' => 'Follow',
        'actor' => $claimedActor,
        'object' => $localActor,
    ], JSON_UNESCAPED_SLASHES);
}

describe('same-host actor spoofing', function () {
    it('rejects a user-inbox activity signed by a different same-host account', function () {
        $user = shsLocalUser();
        $alice = shsStranger('remote.example', 'alice');
        $mallory = shsStranger('remote.example', 'mallory');
        shsSeedProfile($alice, 'remote.example', 'alice');
        shsSeedProfile($mallory, 'remote.example', 'mallory');
        shsSeedHosts();

        $localActor = $user->profile->permalink();
        // Body claims to be from alice, but mallory signs it with her own key.
        $body = shsFollowBody($alice['actor'], $localActor);
        $headers = shsSignedHeaders($mallory, "/users/{$user->profile->username}/inbox", $body);

        shsInProduction(fn () => (new InboxValidator($user->profile->username, $headers, $body))->handle());

        expect(Queue::pushed(ActivityHandler::class))->toBeEmpty();
    });

    it('rejects the same spoofed payload on the shared inbox', function () {
        $user = shsLocalUser();
        $alice = shsStranger('remote.example', 'alice');
        $mallory = shsStranger('remote.example', 'mallory');
        shsSeedProfile($alice, 'remote.example', 'alice');
        shsSeedProfile($mallory, 'remote.example', 'mallory');
        shsSeedHosts();

        $localActor = $user->profile->permalink();
        $body = shsFollowBody($alice['actor'], $localActor);
        $headers = shsSignedHeaders($mallory, '/f/inbox', $body);

        shsInProduction(fn () => (new InboxWorker($headers, $body))->handle());

        expect(Queue::pushed(ActivityHandler::class))->toBeEmpty();
    });

    it('accepts a user-inbox activity when the signer is the claimed actor', function () {
        $user = shsLocalUser();
        $alice = shsStranger('remote.example', 'alice');
        shsSeedProfile($alice, 'remote.example', 'alice');
        shsSeedHosts();

        $localActor = $user->profile->permalink();
        // Alice signs as alice, legitimate same-actor traffic.
        $body = shsFollowBody($alice['actor'], $localActor);
        $headers = shsSignedHeaders($alice, "/users/{$user->profile->username}/inbox", $body);

        shsInProduction(fn () => (new InboxValidator($user->profile->username, $headers, $body))->handle());

        expect(Queue::pushed(ActivityHandler::class))->not->toBeEmpty();
    });
});
