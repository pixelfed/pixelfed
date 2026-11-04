<?php

use App\Jobs\InboxPipeline\InboxValidator;
use App\Jobs\InboxPipeline\InboxWorker;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Redis::spy();
    Queue::fake();
    Http::fake();

    config([
        'instance.enable_cc' => false,
        'federation.activitypub.enabled' => true,
    ]);

    Cache::put('instances:banned:domains', [], 1209600);
});

function inboxRejectionPost($test, string $endpoint, array $headers = [])
{
    $body = json_encode([
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => 'https://remote.example/users/alice#follow',
        'type' => 'Follow',
        'actor' => 'https://remote.example/users/alice',
        'object' => 'https://'.config('pixelfed.domain.app').'/users/bob',
    ], JSON_UNESCAPED_SLASHES);

    $server = ['CONTENT_TYPE' => 'application/activity+json'];
    foreach ($headers as $name => $value) {
        $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
    }

    return $test->call('POST', str_replace('{username}', 'bob', $endpoint), [], [], [], $server, $body);
}

it('rejects an unsigned inbox post with a 401', function (string $endpoint) {
    inboxRejectionPost($this, $endpoint)->assertUnauthorized();

    Queue::assertNotPushed(InboxValidator::class);
    Queue::assertNotPushed(InboxWorker::class);
})->with(['/f/inbox', '/users/{username}/inbox']);

it('rejects an RFC 9421 signed inbox post with a 401 so senders can fall back', function (string $endpoint) {
    inboxRejectionPost($this, $endpoint, [
        'Date' => now()->toRfc7231String(),
        'Signature-Input' => 'sig1=("@method" "@target-uri" "content-digest");keyid="https://remote.example/users/alice#main-key";alg="rsa-v1_5-sha256"',
        'Signature' => 'sig1=:'.base64_encode('sig').':',
    ])->assertUnauthorized();

    Queue::assertNotPushed(InboxValidator::class);
    Queue::assertNotPushed(InboxWorker::class);
})->with(['/f/inbox', '/users/{username}/inbox']);

it('rejects an inbox post with a malformed signature', function (string $endpoint, array $headers) {
    inboxRejectionPost($this, $endpoint, $headers)->assertUnauthorized();

    Queue::assertNotPushed(InboxValidator::class);
    Queue::assertNotPushed(InboxWorker::class);
})->with(['/f/inbox', '/users/{username}/inbox'])->with([
    'no keyId' => fn () => ['Date' => now()->toRfc7231String(), 'Signature' => 'headers="(request-target) host date",signature="YWJj"'],
    'keyId is not a url' => fn () => ['Date' => now()->toRfc7231String(), 'Signature' => 'keyId="nope",headers="(request-target) host date",signature="YWJj"'],
    'no headers' => fn () => ['Date' => now()->toRfc7231String(), 'Signature' => 'keyId="https://remote.example/users/alice#main-key",signature="YWJj"'],
    'no signature' => fn () => ['Date' => now()->toRfc7231String(), 'Signature' => 'keyId="https://remote.example/users/alice#main-key",headers="(request-target) host date"'],
    'no Date header' => fn () => ['Signature' => 'keyId="https://remote.example/users/alice#main-key",headers="(request-target) host date",signature="YWJj"'],
]);

it('still queues an inbox post with a draft-cavage signature', function (string $endpoint, string $job) {
    inboxRejectionPost($this, $endpoint, [
        'Date' => now()->toRfc7231String(),
        'Signature' => 'keyId="https://remote.example/users/alice#main-key",algorithm="rsa-sha256",headers="(request-target) host date digest",signature="'.base64_encode('sig').'"',
    ])->assertOk();

    Queue::assertPushed($job, 1);
})->with([
    ['/f/inbox', InboxWorker::class],
    ['/users/{username}/inbox', InboxValidator::class],
]);
