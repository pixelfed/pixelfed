<?php

namespace App\Jobs\InboxPipeline;

use App\Jobs\DeletePipeline\DeleteRemoteProfilePipeline;
use App\Jobs\InboxPipeline\Concerns\MatchesActorUrl;
use App\Models\Profile;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteWorker implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, MatchesActorUrl, Queueable, SerializesModels;

    protected $headers;

    protected $payload;

    protected $inboxPath = '/f/inbox';

    public $timeout = 300;

    public $tries = 1;

    public $maxExceptions = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($headers, $payload, ?string $inboxPath = null)
    {
        $this->headers = $headers;
        $this->payload = $payload;
        if ($inboxPath) {
            $this->inboxPath = $inboxPath;
        }
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
        $payload = $this->payload;

        // Verify headers exist
        if (! $headers) {
            Log::info('DeleteWorker: Headers not provided, skipping job');

            return;
        }

        // Verify payload exists
        if (! $payload) {
            Log::info('DeleteWorker: Payload not provided, skipping job');

            return;
        }

        $payload = json_decode($payload, true, 8);

        if (! isset($headers['signature']) || ! isset($headers['date'])) {
            Log::info('DeleteWorker: Missing signature or date in headers, skipping job');

            return;
        }

        if (! $headers || ! $payload) {
            Log::info('DeleteWorker: Empty headers or payload, skipping job');

            return;
        }

        if (
            $payload['type'] === 'Delete' &&
            ((is_string($payload['object']) &&
                $payload['object'] === $payload['actor']) ||
                (is_array($payload['object']) &&
                    isset($payload['object']['id'], $payload['object']['type']) &&
                    $payload['object']['type'] === 'Person' &&
                    $payload['actor'] === $payload['object']['id']
                ))
        ) {
            $actor = $payload['actor'];
            if ($this->verifySignature($headers, $payload) == true) {
                $actorDelete = Profile::whereRemoteUrl($actor)->exists();
                if ($actorDelete) {
                    if ($this->verifySignature($headers, $payload) == true) {
                        $profile = Profile::whereNotNull('domain')
                            ->whereNull('status')
                            ->whereRemoteUrl($actor)
                            ->first();
                        if ($profile) {
                            DeleteRemoteProfilePipeline::dispatch($profile)->onQueue('inbox');
                        }

                        return;
                    }

                    // Signature verification failed, exit.
                    return;
                }

                // Remote user doesn't exist, exit early.
                return;
            }

            return;
        }
        $profile = null;
        if ($this->verifySignature($headers, $payload) == true) {
            ActivityHandler::dispatch($headers, $profile, $payload)->onQueue('delete');

            return;
        }
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
        if (! isset($bodyDecoded['id'], $bodyDecoded['actor'])) {
            return false;
        }
        $signatureData = HttpSignature::parseSignatureHeader($signature);

        if (! isset($signatureData['keyId'], $signatureData['signature'], $signatureData['headers']) || isset($signatureData['error'])) {
            return false;
        }

        $keyId = Helpers::validateUrl($signatureData['keyId']);
        $id = Helpers::validateUrl($bodyDecoded['id']);
        $claimedActor = self::actorUrl($bodyDecoded['actor']);
        $claimedActor = $claimedActor ? Helpers::validateUrl($claimedActor) : null;
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
            $attr = Helpers::pluckval($bodyDecoded['object']['attributedTo']);
            if (is_array($attr)) {
                if (isset($attr['id'])) {
                    $attr = $attr['id'];
                } else {
                    $attr = '';
                }
            }
            if (parse_url($attr, PHP_URL_HOST) !== $keyDomain) {
                return false;
            }
        }
        if (
            ! $keyDomain || ! $idDomain || ! $actorDomain
            || $keyDomain !== $idDomain || $keyDomain !== $actorDomain
        ) {
            return false;
        }
        $signer = Profile::whereKeyId($keyId)->first();
        if (! $signer) {
            $signer = Helpers::profileFirstOrNew($claimedActor);
        }
        if (! $signer) {
            return false;
        }
        if (! self::sameActorUrl($signer->remote_url, $claimedActor)) {
            return false;
        }
        $pkey = openssl_pkey_get_public($signer->public_key);
        if (! $pkey) {
            return false;
        }
        [$verified, $headers] = HttpSignature::verify($pkey, $signatureData, $headers, $this->inboxPath, $body);
        if ($verified == 1) {
            return true;
        }

        return false;
    }
}
