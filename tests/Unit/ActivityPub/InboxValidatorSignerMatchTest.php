<?php

namespace Tests\Unit\ActivityPub;

use App\Jobs\InboxPipeline\InboxValidator;
use App\Profile;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InboxValidatorSignerMatchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that verifySignature rejects a valid signature when the signer
     * (keyId) does not match the payload actor, preventing spoofing.
     */
    #[Test]
    public function it_rejects_valid_signature_when_signer_does_not_match_payload_actor()
    {
        // Generate attacker keypair
        $attackerKeyPair = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($attackerKeyPair, $attackerPrivateKey);
        $attackerPublicKey = openssl_pkey_get_details($attackerKeyPair)['key'];

        // Create local profile (inbox recipient)
        $localProfile = Profile::create([
            'username' => 'localuser',
            'domain' => null,
            'remote_url' => null,
        ]);

        // Create attacker profile (already known to the instance)
        $attackerProfile = Profile::create([
            'username' => 'attacker',
            'domain' => 'evil.example.com',
            'remote_url' => 'https://evil.example.com/users/attacker',
            'key_id' => 'https://evil.example.com/users/attacker#main-key',
            'public_key' => $attackerPublicKey,
            'private_key' => $attackerPrivateKey,
        ]);

        // Spoofed payload: signed by attacker but claims to be from victim
        // The activity id domain matches the keyId domain (evil.example.com)
        // to pass the existing keyDomain === idDomain check
        $payload = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => 'https://evil.example.com/activities/spoofed-follow',
            'type' => 'Follow',
            'actor' => 'https://trusted.example.com/users/victim',
            'object' => config('app.url').'/users/localuser',
        ];
        $jsonPayload = json_encode($payload);

        $url = config('app.url')."/users/{$localProfile->username}/inbox";
        $keyId = 'https://evil.example.com/users/attacker#main-key';

        // Sign the request with the attacker's key
        $signedHeaders = HttpSignature::signRaw(
            $attackerPrivateKey,
            $keyId,
            $url,
            $jsonPayload
        );

        // Convert curl-style headers to associative array format
        $assocHeaders = [];
        foreach ($signedHeaders as $header) {
            [$name, $value] = explode(': ', $header, 2);
            $assocHeaders[strtolower($name)] = [$value];
        }

        // Use reflection to call the protected verifySignature method directly
        $job = new InboxValidator($localProfile->username, $assocHeaders, $jsonPayload);
        $reflection = new \ReflectionMethod($job, 'verifySignature');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($job, $assocHeaders, $localProfile, $payload);

        // The signature is cryptographically valid (attacker's key matches),
        // but the signer domain (evil.example.com) does not match the payload
        // actor domain (trusted.example.com), so it MUST be rejected.
        $this->assertFalse(
            $result,
            'verifySignature should reject a valid signature when the signer does not match the payload actor'
        );
    }

    /**
     * Test that verifySignature accepts a valid signature when the signer
     * matches the payload actor (legitimate request).
     */
    #[Test]
    public function it_accepts_valid_signature_when_signer_matches_payload_actor()
    {
        // Generate keypair for legitimate actor
        $keyPair = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($keyPair, $privateKey);
        $publicKey = openssl_pkey_get_details($keyPair)['key'];

        // Create local profile (inbox recipient)
        $localProfile = Profile::create([
            'username' => 'localuser',
            'domain' => null,
            'remote_url' => null,
        ]);

        // Create legitimate remote profile
        $remoteProfile = Profile::create([
            'username' => 'legitimate',
            'domain' => 'legit.example.com',
            'remote_url' => 'https://legit.example.com/users/legitimate',
            'key_id' => 'https://legit.example.com/users/legitimate#main-key',
            'public_key' => $publicKey,
            'private_key' => $privateKey,
        ]);

        // Legitimate payload: signer and actor are the same
        $payload = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => 'https://legit.example.com/activities/real-follow',
            'type' => 'Follow',
            'actor' => 'https://legit.example.com/users/legitimate',
            'object' => config('app.url').'/users/localuser',
        ];
        $jsonPayload = json_encode($payload);

        $url = config('app.url')."/users/{$localProfile->username}/inbox";
        $keyId = 'https://legit.example.com/users/legitimate#main-key';

        $signedHeaders = HttpSignature::signRaw(
            $privateKey,
            $keyId,
            $url,
            $jsonPayload
        );

        $assocHeaders = [];
        foreach ($signedHeaders as $header) {
            [$name, $value] = explode(': ', $header, 2);
            $assocHeaders[strtolower($name)] = [$value];
        }

        $job = new InboxValidator($localProfile->username, $assocHeaders, $jsonPayload);
        $reflection = new \ReflectionMethod($job, 'verifySignature');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($job, $assocHeaders, $localProfile, $payload);

        // Signer and actor match — should be accepted
        $this->assertTrue(
            $result,
            'verifySignature should accept a valid signature when the signer matches the payload actor'
        );
    }
}
