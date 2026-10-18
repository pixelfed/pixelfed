<?php

use App\Jobs\InboxPipeline\DeleteWorker;
use App\Models\Profile;
use App\Util\ActivityPub\Helpers;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| String-object Delete ingress
|--------------------------------------------------------------------------
|
| The canonical account self-delete, the one Mastodon sends and the one
| Pixelfed's own outbound builder emits, carries the actor id as a plain
| string object. The inbox endpoints have to forward that to DeleteWorker,
| not drop it, while still refusing deletes whose object is not the actor
| or whose actor is unknown to this server.
|
*/

beforeEach(function () {
    Redis::spy();
    Queue::fake();
    Http::fake();

    config([
        'instance.enable_cc' => false,
        'federation.activitypub.enabled' => true,
    ]);
});

function stringDeleteRemoteProfile(string $username = 'alice', string $domain = 'remote.example'): Profile
{
    $actor = "https://{$domain}/users/{$username}";

    return Profile::factory()->remote()->create([
        'domain' => $domain,
        'username' => "@{$username}@{$domain}",
        'remote_url' => $actor,
        'key_id' => "{$actor}#main-key",
        'inbox_url' => "{$actor}/inbox",
        'sharedInbox' => "https://{$domain}/inbox",
        'last_fetched_at' => now(),
    ]);
}

function stringDeleteSeedHosts(array $hosts = ['remote.example']): void
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
 * POST a signed activity to an inbox endpoint, signing over the exact path
 * the request is delivered to so DeleteWorker can verify it downstream.
 */
function stringDeleteSignedPost($test, Profile $actor, $privateKey, string $path, array $activity)
{
    $body = json_encode($activity, JSON_UNESCAPED_SLASHES);
    $host = parse_url(config('app.url'), PHP_URL_HOST);
    $date = now()->toRfc7231String();
    $digest = 'SHA-256='.base64_encode(hash('sha256', $body, true));

    $signingString = implode("\n", [
        '(request-target): post '.$path,
        'host: '.$host,
        'date: '.$date,
        'digest: '.$digest,
    ]);

    openssl_sign($signingString, $signature, $privateKey, OPENSSL_ALGO_SHA256);

    return $test->call('POST', $path, [], [], [], [
        'HTTP_HOST' => $host,
        'HTTP_DATE' => $date,
        'HTTP_DIGEST' => $digest,
        'HTTP_SIGNATURE' => 'keyId="'.$actor->key_id.'",algorithm="rsa-sha256",headers="(request-target) host date digest",signature="'.base64_encode($signature).'"',
        'CONTENT_TYPE' => 'application/activity+json',
        'HTTP_ACCEPT' => 'application/activity+json',
    ], $body);
}

function stringDeleteKeyedProfile(): array
{
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);

    $alice = stringDeleteRemoteProfile();
    $alice->public_key = openssl_pkey_get_details($key)['key'];
    $alice->save();

    stringDeleteSeedHosts();

    return [$alice, $key];
}

function stringDeleteActivity(Profile $actor, mixed $object): array
{
    return [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => $actor->remote_url.'#delete',
        'type' => 'Delete',
        'actor' => $actor->remote_url,
        'to' => ['https://www.w3.org/ns/activitystreams#Public'],
        'object' => $object,
    ];
}

function stringDeletePersonObject(Profile $actor): array
{
    return [
        'id' => $actor->remote_url,
        'type' => 'Person',
    ];
}

it('queues a signed Delete whose object is the actor string', function (string $endpoint) {
    [$alice, $key] = stringDeleteKeyedProfile();

    $response = stringDeleteSignedPost(
        $this,
        $alice,
        $key,
        str_replace('{username}', 'placeholder', $endpoint),
        stringDeleteActivity($alice, $alice->remote_url)
    );

    $response->assertOk();

    expect(Queue::pushed(DeleteWorker::class)->count())->toBe(1);
})->with([
    '/f/inbox',
    '/users/{username}/inbox',
]);

it('queues a signed Delete whose object is a Person array (control)', function (string $endpoint) {
    [$alice, $key] = stringDeleteKeyedProfile();

    $response = stringDeleteSignedPost(
        $this,
        $alice,
        $key,
        str_replace('{username}', 'placeholder', $endpoint),
        stringDeleteActivity($alice, stringDeletePersonObject($alice))
    );

    $response->assertOk();

    expect(Queue::pushed(DeleteWorker::class)->count())->toBe(1);
})->with([
    '/f/inbox',
    '/users/{username}/inbox',
]);

it('drops a signed string Delete whose object is not the actor', function (string $endpoint) {
    [$alice, $key] = stringDeleteKeyedProfile();

    $response = stringDeleteSignedPost(
        $this,
        $alice,
        $key,
        str_replace('{username}', 'placeholder', $endpoint),
        stringDeleteActivity($alice, 'https://remote.example/users/someone-else')
    );

    $response->assertOk();

    Queue::assertNotPushed(DeleteWorker::class);
})->with([
    '/f/inbox',
    '/users/{username}/inbox',
]);

it('drops a signed string Delete whose actor is unknown to this server', function (string $endpoint) {
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    stringDeleteSeedHosts();

    $actorUrl = 'https://remote.example/users/ghost';
    $activity = [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => $actorUrl.'#delete',
        'type' => 'Delete',
        'actor' => $actorUrl,
        'to' => ['https://www.w3.org/ns/activitystreams#Public'],
        'object' => $actorUrl,
    ];

    $ghost = new Profile;
    $ghost->key_id = $actorUrl.'#main-key';

    $response = stringDeleteSignedPost(
        $this,
        $ghost,
        $key,
        str_replace('{username}', 'placeholder', $endpoint),
        $activity
    );

    $response->assertOk();

    Queue::assertNotPushed(DeleteWorker::class);
})->with([
    '/f/inbox',
    '/users/{username}/inbox',
]);
