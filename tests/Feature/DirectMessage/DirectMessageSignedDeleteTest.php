<?php

use App\Jobs\InboxPipeline\ActivityHandler;
use App\Jobs\InboxPipeline\DeleteWorker;
use App\Models\DmMessage;
use App\Models\Profile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

require_once __DIR__.'/helpers.php';

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| A signed Delete, from the HTTP request to the message being gone
|--------------------------------------------------------------------------
|
| The other inbound tests hand an activity straight to the Inbox. A Delete
| has two more places to die before it gets there: the inbox endpoint only
| queues deletes for things this server has, and the DeleteWorker verifies
| the HTTP signature, which covers the path the request was sent to. Servers
| deliver direct messages to the recipient's own inbox (Loops always does),
| so that is the path that has to verify, not only the shared inbox.
|
*/

beforeEach(function () {
    Redis::spy();
    Queue::fake();
    Http::fake();

    config([
        'snowflake.datacenter_id' => 1,
        'snowflake.worker_id' => 1,
        'instance.enable_cc' => false,
        'federation.activitypub.enabled' => true,
    ]);
});

function dmSignedPost($test, Profile $actor, $privateKey, string $path, array $activity)
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

it('deletes a direct message from a signed Delete', function (string $endpoint) {
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);

    $bobUser = dmLocalUser();
    $bob = dmProfile($bobUser);
    $alice = dmRemoteProfile();
    $alice->public_key = openssl_pkey_get_details($key)['key'];
    $alice->save();
    dmSeedHosts();

    dmDeliver($alice, dmNote($alice, '1', [$bob]));
    expect(DmMessage::count())->toBe(1);

    $response = dmSignedPost($this, $alice, $key, str_replace('{username}', $bobUser->username, $endpoint), [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => $alice->remote_url.'/statuses/1#delete',
        'type' => 'Delete',
        'actor' => $alice->remote_url,
        'to' => [$bob->permalink()],
        'object' => ['id' => $alice->remote_url.'/statuses/1', 'type' => 'Tombstone'],
    ]);

    $response->assertOk();

    // The endpoint queued it
    $worker = Queue::pushed(DeleteWorker::class)->first();
    expect($worker)->not->toBeNull();

    // The signature verified against the path it was delivered to
    $worker->handle();
    $handler = Queue::pushed(ActivityHandler::class)->first();
    expect($handler)->not->toBeNull();

    // And the inbox removed the message
    $handler->handle();
    expect(DmMessage::count())->toBe(0);
})->with(['/f/inbox', '/users/{username}/inbox']);

it('rejects a Delete whose signature was made for a different inbox', function () {
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);

    $bobUser = dmLocalUser();
    $bob = dmProfile($bobUser);
    $alice = dmRemoteProfile();
    $alice->public_key = openssl_pkey_get_details($key)['key'];
    $alice->save();
    dmSeedHosts();

    dmDeliver($alice, dmNote($alice, '1', [$bob]));

    $activity = [
        'id' => $alice->remote_url.'/statuses/1#delete',
        'type' => 'Delete',
        'actor' => $alice->remote_url,
        'object' => ['id' => $alice->remote_url.'/statuses/1', 'type' => 'Tombstone'],
    ];

    // Signed for the shared inbox, replayed at a personal one
    $body = json_encode($activity, JSON_UNESCAPED_SLASHES);
    $host = parse_url(config('app.url'), PHP_URL_HOST);
    $date = now()->toRfc7231String();
    $digest = 'SHA-256='.base64_encode(hash('sha256', $body, true));
    openssl_sign("(request-target): post /f/inbox\nhost: {$host}\ndate: {$date}\ndigest: {$digest}", $signature, $key, OPENSSL_ALGO_SHA256);

    $this->call('POST', '/users/'.$bobUser->username.'/inbox', [], [], [], [
        'HTTP_HOST' => $host,
        'HTTP_DATE' => $date,
        'HTTP_DIGEST' => $digest,
        'HTTP_SIGNATURE' => 'keyId="'.$alice->key_id.'",algorithm="rsa-sha256",headers="(request-target) host date digest",signature="'.base64_encode($signature).'"',
        'CONTENT_TYPE' => 'application/activity+json',
    ], $body)->assertOk();

    Queue::pushed(DeleteWorker::class)->first()->handle();

    Queue::assertNotPushed(ActivityHandler::class);
    expect(DmMessage::count())->toBe(1);
});
