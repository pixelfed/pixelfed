<?php

use App\Console\Commands\User\UserAccountDelete;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| UserAccountDelete outbound User-Agent
|--------------------------------------------------------------------------
|
| The federated account-deletion POSTs must carry the Pixelfed User-Agent
| (set per-request via the HTTP signature), not Guzzle's default. Http::pool
| does not inherit the makeHttpClient instance headers.
|
*/

function generatePrivateKeyPem(): string
{
    $res = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
    openssl_pkey_export($res, $pem);

    return $pem;
}

it('sends the Pixelfed User-Agent on pooled delete deliveries', function () {
    Http::fake([
        '*' => Http::response('', 202),
    ]);

    $privateKey = generatePrivateKeyPem();
    $keyId = 'https://local.test/users/alice#main-key';
    $payload = '{"type":"Delete"}';
    $digest = base64_encode(hash('sha256', $payload, true));

    $urls = collect(['https://remote.example/f/inbox']);

    $command = app(UserAccountDelete::class);
    $ref = new ReflectionMethod($command, 'sendBatch');
    $ref->setAccessible(true);

    // sendBatch(PendingRequest $client, string $privateKey, string $keyId,
    //   string $digest, Collection $urls, string $payload, int $payloadLen,
    //   int $concurrency, bool $verboseErrors)
    $client = (new ReflectionMethod($command, 'makeHttpClient'));
    $client->setAccessible(true);
    $pendingClient = $client->invoke($command);

    $ref->invoke(
        $command,
        $pendingClient,
        $privateKey,
        $keyId,
        $digest,
        $urls,
        $payload,
        strlen($payload),
        5,
        false
    );

    Http::assertSent(function ($request) {
        return $request->url() === 'https://remote.example/f/inbox'
            && $request->hasHeader('User-Agent')
            && str_contains($request->header('User-Agent')[0], 'Pixelfed');
    });
});
