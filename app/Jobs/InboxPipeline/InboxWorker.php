<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Jobs\InboxPipeline;

use Cache;
use App\Profile;
use App\Util\ActivityPub\{
    Helpers,
    HttpSignature,
    Inbox
};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Zttp\Zttp;
use App\Jobs\DeletePipeline\DeleteRemoteProfilePipeline;

class InboxWorker implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $headers;
    protected $payload;

    public $timeout = 60;
    public $tries = 1;

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
        $payload = json_decode($this->payload, true, 8);

        if(isset($payload['id'])) {
            $lockKey = hash('sha256', $payload['id']);
            if(Cache::get($lockKey) !== null) {
                // Job processed already
                return 1;
            }
            Cache::put($lockKey, 1, 300);
        }

        if(!isset($headers['signature']) || !isset($headers['date'])) {
            return;
        }

        if(empty($headers) || empty($payload)) {
            return;
        }

        if( $payload['type'] === 'Delete' &&
            ( ( is_string($payload['object']) &&
                $payload['object'] === $payload['actor'] ) ||
            ( is_array($payload['object']) &&
              isset($payload['object']['id'], $payload['object']['type']) &&
              $payload['object']['type'] === 'Person' &&
              $payload['actor'] === $payload['object']['id']
            ))
        ) {
            $actor = $payload['actor'];
            $hash = strlen($actor) <= 48 ? 
                'b:' . base64_encode($actor) :
                'h:' . hash('sha256', $actor);

            $lockKey = 'ap:inbox:actor-delete-exists:lock:' . $hash;
            Cache::lock($lockKey, 10)->block(5, function () use(
                $headers,
                $payload,
                $actor,
                $hash
            ) {
                $key = 'ap:inbox:actor-delete-exists:' . $hash;
                $actorDelete = Cache::remember($key, now()->addMinutes(15), function() use($actor) {
                    return Profile::whereRemoteUrl($actor)
                        ->whereNotNull('domain')
                        ->exists();
                });
                if($actorDelete) {
                    if($this->verifySignature($headers, $payload) == true) {
                        Cache::set($key, false);
                        $profile = Profile::whereNotNull('domain')
                            ->whereNull('status')
                            ->whereRemoteUrl($actor)
                            ->first();
                        if($profile) {
                            DeleteRemoteProfilePipeline::dispatchNow($profile);
                        }
                        return;
                    } else {
                        // Signature verification failed, exit.
                        return;
                    }
                } else {
                    // Remote user doesn't exist, exit early.
                    return;
                }
            });

            return;
        }

        if($this->verifySignature($headers, $payload) == true) {
            (new Inbox($headers, $profile, $payload))->handle();
            return;
        } else if($this->blindKeyRotation($headers, $payload) == true) {
            (new Inbox($headers, $profile, $payload))->handle();
            return;
        } else {
            return;
        }
    }

    protected function verifySignature($headers, $payload)
    {
        $body = $this->payload;
        $bodyDecoded = $payload;
        $signature = is_array($headers['signature']) ? $headers['signature'][0] : $headers['signature'];
        $date = is_array($headers['date']) ? $headers['date'][0] : $headers['date'];
        if(!$signature) {
            return;
        }
        if(!$date) {
            return;
        }
        if(!now()->parse($date)->gt(now()->subDays(1)) || 
           !now()->parse($date)->lt(now()->addDays(1))
       ) {
            return;
        }
        $signatureData = HttpSignature::parseSignatureHeader($signature);
        $keyId = Helpers::validateUrl($signatureData['keyId']);
        $id = Helpers::validateUrl($bodyDecoded['id']);
        $keyDomain = parse_url($keyId, PHP_URL_HOST);
        $idDomain = parse_url($id, PHP_URL_HOST);
        if(isset($bodyDecoded['object']) 
            && is_array($bodyDecoded['object'])
            && isset($bodyDecoded['object']['attributedTo'])
        ) {
            if(parse_url($bodyDecoded['object']['attributedTo'], PHP_URL_HOST) !== $keyDomain) {
                return;
            }
        }
        if(!$keyDomain || !$idDomain || $keyDomain !== $idDomain) {
            return;
        }
        $actor = Profile::whereKeyId($keyId)->first();
        if(!$actor) {
            $actorUrl = is_array($bodyDecoded['actor']) ? $bodyDecoded['actor'][0] : $bodyDecoded['actor'];
            $actor = Helpers::profileFirstOrNew($actorUrl);
        }
        if(!$actor) {
            return;
        }
        $pkey = openssl_pkey_get_public($actor->public_key);
        if(!$pkey) {
            return 0;
        }
        $inboxPath = "/f/inbox";
        list($verified, $headers) = HttpSignature::verify($pkey, $signatureData, $headers, $inboxPath, $body);
        if($verified == 1) { 
            return true;
        } else {
            return false;
        }
    }

    protected function blindKeyRotation($headers, $payload)
    {
        $signature = is_array($headers['signature']) ? $headers['signature'][0] : $headers['signature'];
        $date = is_array($headers['date']) ? $headers['date'][0] : $headers['date'];
        if(!$signature) {
            return;
        }
        if(!$date) {
            return;
        }
        if(!now()->parse($date)->gt(now()->subDays(1)) || 
           !now()->parse($date)->lt(now()->addDays(1))
       ) {
            return;
        }
        $signatureData = HttpSignature::parseSignatureHeader($signature);
        $keyId = Helpers::validateUrl($signatureData['keyId']);
        $actor = Profile::whereKeyId($keyId)->whereNotNull('remote_url')->first();
        if(!$actor) {
            return;
        }
        if(Helpers::validateUrl($actor->remote_url) == false) {
            return;
        }
        $res = Zttp::timeout(5)->withHeaders([
          'Accept'     => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
          'User-Agent' => 'PixelfedBot v0.1 - https://pixelfed.org',
        ])->get($actor->remote_url);
        $res = json_decode($res->body(), true, 8);
        if($res['publicKey']['id'] !== $actor->key_id) {
            return;
        }
        $actor->public_key = $res['publicKey']['publicKeyPem'];
        $actor->save();
        return $this->verifySignature($headers, $payload);
    }
}
