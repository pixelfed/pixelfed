<?php

namespace App\Jobs\InboxPipeline;

use App\Jobs\InboxPipeline\Concerns\RetriesWhenActorUnavailable;
use App\Models\Profile;
use App\Services\FollowersSyncService;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class InboxWorker implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use RetriesWhenActorUnavailable;

    protected $headers;

    protected $payload;

    public $timeout = 300;

    // One attempt plus the retries in RetriesWhenActorUnavailable. Exceptions
    // still fail the job immediately because of $maxExceptions below.
    public $tries = 4;

    public $maxExceptions = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($headers, $payload)
    {
        $this->headers = $headers;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $profile = null;
        $headers = $this->headers;

        if (empty($headers) || empty($this->payload) || ! isset($headers['signature']) || ! isset($headers['date'])) {
            return;
        }

        $payload = json_decode($this->payload, true, 8);

        if ($this->verifySignature($headers, $payload) == true) {
            if (isset($payload['id'])) {
                $lockKey = 'pf:ap:user-inbox:activity:'.hash('sha256', $payload['id']);
                if (! Cache::add($lockKey, 1, 3600)) {
                    // Already processed after valid signature check
                    return;
                }
            }

            // FEP-8fcf: compare the sender's followers digest with our copy
            FollowersSyncService::handleInboundHeaders($headers);

            ActivityHandler::dispatch($headers, $profile, $payload)->onQueue('shared');

            return;
        }

        $this->retryLaterIfActorUnavailable();
    }

    protected function verifySignature($headers, $payload)
    {
        $body = $this->payload;
        $bodyDecoded = $payload;
        $signature = is_array($headers['signature']) ? $headers['signature'][0] : $headers['signature'];
        $date = is_array($headers['date']) ? $headers['date'][0] : $headers['date'];
        if (! $signature) {
            return false;
        }
        if (! $date) {
            return false;
        }
        if (
            ! now()->parse($date)->gt(now()->subDays(1)) ||
            ! now()->parse($date)->lt(now()->addDays(1))
        ) {
            return false;
        }
        if (! isset($bodyDecoded['id']) || ! isset($bodyDecoded['actor'])) {
            return false;
        }
        $signatureData = HttpSignature::parseSignatureHeader($signature);

        if (! isset($signatureData['keyId'], $signatureData['signature'], $signatureData['headers']) || isset($signatureData['error'])) {
            return false;
        }

        $keyId = Helpers::validateUrl($signatureData['keyId']);

        $claimedActor = self::actorUrl($bodyDecoded['actor'] ?? null);
        if (! $claimedActor && $keyId && InboxValidator::actorOptionalFor($bodyDecoded)) {
            $claimedActor = strtok($keyId, '#');
        }
        if (! $claimedActor) {
            return false;
        }

        $id = Helpers::validateUrl($bodyDecoded['id']);
        $claimedActor = Helpers::validateUrl($claimedActor);
        if (! $keyId || ! $id || ! $claimedActor) {
            return false;
        }

        $keyDomain = parse_url($keyId, PHP_URL_HOST);
        $idDomain = parse_url($id, PHP_URL_HOST);
        $actorDomain = parse_url($claimedActor, PHP_URL_HOST);
        if (
            isset($bodyDecoded['object'])
            && is_array($bodyDecoded['object'])
            && isset($bodyDecoded['object']['attributedTo'])
        ) {
            $attr = self::actorUrl($bodyDecoded['object']['attributedTo']);
            if (! $attr || parse_url($attr, PHP_URL_HOST) !== $keyDomain) {
                return false;
            }
        }
        if (
            ! $keyDomain || ! $idDomain || ! $actorDomain
            || $keyDomain !== $idDomain || $keyDomain !== $actorDomain
        ) {
            return false;
        }

        // Resolve the profile that owns the signing key.
        $signer = Profile::whereKeyId($keyId)->first();
        if (! $signer) {
            $signer = Helpers::profileFirstOrNew($claimedActor);
        }
        if (! $signer) {
            $this->markActorUnavailable($claimedActor);

            return false;
        }

        // The key owner MUST be the actor the activity claims to be from.
        // A same-host check is not enough: every account on a multi-user
        // instance shares $keyDomain. This subsumes the old rebind check,
        // since a row whose remote_url is on another host can never equal
        // $claimedActor.
        if (! self::sameActorUrl($signer->remote_url, $claimedActor)) {
            return false;
        }

        $pkey = openssl_pkey_get_public($signer->public_key);
        if (! $pkey) {
            return false;
        }
        $inboxPath = '/f/inbox';
        [$verified, $headers] = HttpSignature::verify($pkey, $signatureData, $headers, $inboxPath, $body);
        if ($verified == 1) {
            return true;
        }

        return false;
    }

    /**
     * Extract an actor URL from a string, a {"id": ...} object, or a list.
     */
    protected static function actorUrl($val)
    {
        $val = Helpers::pluckval($val);
        if (is_array($val)) {
            $val = $val['id'] ?? null;
        }

        return is_string($val) && $val !== '' ? $val : null;
    }

    /**
     * Exact actor identity match. Scheme and host are case-insensitive,
     * path is not, a single trailing slash is ignored. Query and fragment
     * are part of the comparison so they cannot be used to alias an actor.
     */
    protected static function sameActorUrl($a, $b): bool
    {
        $a = self::normalizeUrl($a);
        $b = self::normalizeUrl($b);

        return $a !== null && $b !== null && $a === $b;
    }

    protected static function normalizeUrl($url)
    {
        if (! is_string($url) || $url === '') {
            return null;
        }
        $p = parse_url($url);
        if (! $p || empty($p['scheme']) || empty($p['host'])) {
            return null;
        }
        $out = strtolower($p['scheme']).'://'.strtolower($p['host']);
        if (isset($p['port'])) {
            $out .= ':'.$p['port'];
        }
        $out .= rtrim($p['path'] ?? '', '/');
        if (isset($p['query'])) {
            $out .= '?'.$p['query'];
        }
        if (isset($p['fragment'])) {
            $out .= '#'.$p['fragment'];
        }

        return $out;
    }
}
