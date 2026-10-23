<?php

use App\Util\ActivityPub\Helpers;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Profile key_id host validation
|--------------------------------------------------------------------------
|
| isValidProfileData() gates first-contact remote actor ingestion. Besides
| requiring the actor `id` host to match the fetched URL host, it must also
| require the actor `publicKey.id` host to match the actor `id` host.
|
| Without this guard a remote actor could advertise a publicKey.id pointing at
| a victim's keyId URI (e.g. https://victim-host.test/users/alice#main-key)
| with an attacker-controlled publicKeyPem, planting a poisoned
| key_id -> attacker key binding in the unique profiles.key_id column and
| silently black-holing the victim's federation.
|
*/

/**
 * validateUrl() (called internally by isValidProfileData) resolves the host to
 * a public IP. Pre-seed the DNS cache so these tests stay deterministic and
 * network-free while still exercising the host-comparison logic.
 */
function seedResolvableHost(string $host): void
{
    $key = 'helpers:url:public-ips:'.hash('xxh128', $host);
    Cache::put($key, ['203.0.113.10'], 3600);
}

function keyIdActorDoc(array $overrides = []): array
{
    return array_replace_recursive([
        'id' => 'https://attacker-host.test/users/attacker',
        'inbox' => 'https://attacker-host.test/users/attacker/inbox',
        'outbox' => 'https://attacker-host.test/users/attacker/outbox',
        'preferredUsername' => 'attacker',
        'publicKey' => [
            'id' => 'https://attacker-host.test/users/attacker#main-key',
            'owner' => 'https://attacker-host.test/users/attacker',
            'publicKeyPem' => "-----BEGIN PUBLIC KEY-----\nattacker\n-----END PUBLIC KEY-----",
        ],
    ], $overrides);
}

beforeEach(function () {
    seedResolvableHost('attacker-host.test');
    seedResolvableHost('victim-host.test');
});

it('accepts an actor whose publicKey.id host matches its id host', function () {
    $res = keyIdActorDoc();

    expect(Helpers::isValidProfileData($res, $res['id']))->toBeTrue();
});

it('rejects an actor whose publicKey.id host points at another host', function () {
    // The plant attempt: attacker actor advertises the victim's keyId URI.
    $res = keyIdActorDoc([
        'publicKey' => [
            'id' => 'https://victim-host.test/users/alice#main-key',
        ],
    ]);

    expect(Helpers::isValidProfileData($res, $res['id']))->toBeFalse();
});

it('rejects an actor whose publicKey.id is not a valid url', function () {
    $res = keyIdActorDoc([
        'publicKey' => [
            'id' => 'not-a-url',
        ],
    ]);

    expect(Helpers::isValidProfileData($res, $res['id']))->toBeFalse();
});
