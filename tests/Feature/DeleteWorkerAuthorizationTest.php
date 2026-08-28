<?php

use App\Jobs\InboxPipeline\DeleteWorker;
use App\Profile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| DeleteWorker Authorization - Regression Tests
|--------------------------------------------------------------------------
|
| Ensures that DeleteWorker validates the actor domain matches the keyId
| domain, preventing cross-domain profile deletion attacks (W3C AP §B.2).
|
*/

describe('DeleteWorker actor domain validation', function () {
    it('rejects delete when actor domain differs from signature keyId domain', function () {
        $attackerDomain = 'attacker.example';
        $victimDomain = 'victim.example';

        $pki = openssl_pkey_new([
            'digest_alg' => 'sha512',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($pki, $privateKey);
        $publicKeyDetails = openssl_pkey_get_details($pki);
        $publicKey = $publicKeyDetails['key'];

        Profile::factory()->remote()->create([
            'domain' => $attackerDomain,
            'remote_url' => "https://{$attackerDomain}/users/attacker",
            'key_id' => "https://{$attackerDomain}/users/attacker#main-key",
            'public_key' => $publicKey,
        ]);

        Profile::factory()->remote()->create([
            'domain' => $victimDomain,
            'remote_url' => "https://{$victimDomain}/users/victim",
            'key_id' => "https://{$victimDomain}/users/victim#main-key",
        ]);

        // Craft malicious payload: id on attacker domain, actor pointing to victim
        $payload = json_encode([
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => "https://{$attackerDomain}/delete/1",
            'type' => 'Delete',
            'actor' => "https://{$victimDomain}/users/victim",
            'object' => "https://{$victimDomain}/users/victim",
        ]);

        $headers = [
            'signature' => 'keyId="https://'.$attackerDomain.'/users/attacker#main-key",algorithm="rsa-sha256",headers="(request-target) host date digest",signature="fake"',
            'date' => now()->toRfc7231String(),
        ];

        $worker = new DeleteWorker($headers, $payload);
        $worker->handle();

        // Victim profile must still exist — attack was blocked
        expect(Profile::where('remote_url', "https://{$victimDomain}/users/victim")->exists())->toBeTrue();
    });

    it('rejects delete when actor domain is empty', function () {
        $attackerDomain = 'attacker2.example';

        $pki = openssl_pkey_new([
            'digest_alg' => 'sha512',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($pki, $privateKey);
        $publicKeyDetails = openssl_pkey_get_details($pki);
        $publicKey = $publicKeyDetails['key'];

        Profile::factory()->remote()->create([
            'domain' => $attackerDomain,
            'remote_url' => "https://{$attackerDomain}/users/attacker2",
            'key_id' => "https://{$attackerDomain}/users/attacker2#main-key",
            'public_key' => $publicKey,
        ]);

        $payload = json_encode([
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => "https://{$attackerDomain}/delete/2",
            'type' => 'Delete',
            'actor' => '',
            'object' => '',
        ]);

        $headers = [
            'signature' => 'keyId="https://'.$attackerDomain.'/users/attacker2#main-key",algorithm="rsa-sha256",headers="(request-target) host date digest",signature="fake"',
            'date' => now()->toRfc7231String(),
        ];

        $worker = new DeleteWorker($headers, $payload);
        $worker->handle();

        // Profile should still exist
        expect(Profile::where('remote_url', "https://{$attackerDomain}/users/attacker2")->exists())->toBeTrue();
    });
});
