<?php

namespace App\Services;

use App\Jobs\ProfilePipeline\SigningActorDiscoveryPipeline;
use App\Models\Profile;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Verifies draft-cavage HTTP signatures on incoming GET requests.
 *
 * No remote request is ever made while verifying: a signer we have not seen
 * before is discovered in the background and verified on its next attempt.
 */
class ActivityPubSignedFetchService
{
    const DISCOVERY_KEY = 'pf:services:ap-signed-fetch:discover:';

    const DISCOVERY_TTL = 600;

    /**
     * Without these in the signed set a signature could be replayed against
     * another path or host.
     */
    private const REQUIRED_SIGNED_HEADERS = [
        '(request-target)',
        'host',
        'date',
    ];

    private const SUPPORTED_ALGORITHMS = [
        'rsa-sha256',
        'hs2019',
    ];

    /**
     * Return the remote profile that signed the request, or null when the
     * request is unsigned, invalid, or signed by an actor we cannot verify
     * yet.
     */
    public static function verify(Request $request): ?Profile
    {
        $signature = $request->header('Signature');

        if (! is_string($signature) || trim($signature) === '') {
            return null;
        }

        $signatureData = HttpSignature::parseSignatureHeader($signature);

        if (
            isset($signatureData['error'])
            || ! isset($signatureData['keyId'], $signatureData['headers'], $signatureData['signature'])
        ) {
            return null;
        }

        if (
            isset($signatureData['algorithm'])
            && ! in_array(strtolower($signatureData['algorithm']), self::SUPPORTED_ALGORITHMS, true)
        ) {
            return null;
        }

        $signed = preg_split('/\s+/', strtolower(trim($signatureData['headers']))) ?: [];

        foreach (self::REQUIRED_SIGNED_HEADERS as $required) {
            if (! in_array($required, $signed, true)) {
                return null;
            }
        }

        if (! self::hasFreshDate($request->header('Date'))) {
            return null;
        }

        $keyId = Helpers::validateUrl($signatureData['keyId']);

        if (! $keyId) {
            return null;
        }

        $keyHost = strtolower((string) parse_url($keyId, PHP_URL_HOST));

        if (
            $keyHost === ''
            || Helpers::isLocalDomain($keyHost)
            || in_array($keyHost, InstanceService::getBannedDomains())
        ) {
            return null;
        }

        $signer = Profile::whereKeyId($keyId)
            ->whereNotNull('domain')
            ->first();

        if (! $signer) {
            self::discover($keyId);

            return null;
        }

        if ($signer->status !== null || empty($signer->public_key)) {
            return null;
        }

        // Rebind: the profile bound to this keyId must live on the keyId host.
        if (strtolower((string) parse_url((string) $signer->remote_url, PHP_URL_HOST)) !== $keyHost) {
            return null;
        }

        $publicKey = openssl_pkey_get_public($signer->public_key);

        if (! $publicKey) {
            return null;
        }

        try {
            [$verified] = HttpSignature::verify(
                $publicKey,
                $signatureData,
                $request->headers->all(),
                $request->getRequestUri(),
                '',
                'get'
            );
        } catch (Throwable) {
            return null;
        }

        if ($verified !== 1) {
            // The signer may have rotated its key since we last fetched it.
            if (Helpers::needsFetch($signer)) {
                self::discover($keyId);
            }

            return null;
        }

        return $signer;
    }

    protected static function discover(string $keyId): void
    {
        if (! Cache::add(self::DISCOVERY_KEY.hash('sha256', $keyId), 1, self::DISCOVERY_TTL)) {
            return;
        }

        SigningActorDiscoveryPipeline::dispatch($keyId)->onQueue('low');
    }

    /**
     * Not older than 12 hours and not more than an hour in the future.
     */
    protected static function hasFreshDate(mixed $date): bool
    {
        if (! is_string($date) || trim($date) === '') {
            return false;
        }

        try {
            $parsed = Carbon::parse($date);
        } catch (Throwable) {
            return false;
        }

        return $parsed->gt(now()->subHours(12))
            && $parsed->lt(now()->addHour());
    }
}
