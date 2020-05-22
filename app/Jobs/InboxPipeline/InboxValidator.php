<?php

namespace App\Jobs\InboxPipeline;

use App\Profile;
use App\Util\ActivityPub\{
    Helpers,
    HttpSignature,
    Inbox
};
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Zttp\Zttp;

class InboxValidator implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $username;
    protected $headers;
    protected $payload;

    public $timeout = 5;
    public $tries = 1;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($username, $headers, $payload)
    {
        $this->username = $username;
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
        $username = $this->username;
        $headers = $this->headers;
        $payload = json_decode($this->payload, true, 8);

        $profile = Profile::whereNull('domain')->whereUsername($username)->first();

        if(!isset($headers['signature']) || !isset($headers['date'])) {
            return;
        }

        if(empty($profile) || empty($headers) || empty($payload)) {
            return;
        }

        if($profile->status != null) {
            return;
        }

        if($this->verifySignature($headers, $profile, $payload) == true) {
            (new Inbox($headers, $profile, $payload))->handle();
            return;
        } else if($this->blindKeyRotation($headers, $profile, $payload) == true) {
            (new Inbox($headers, $profile, $payload))->handle();
            return;
        } else {
            return;
        }

    }

    protected function verifySignature($headers, $profile, $payload)
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
                abort(400, 'Invalid request');
            }
        }
        if(!$keyDomain || !$idDomain || $keyDomain !== $idDomain) {
            return;
            abort(400, 'Invalid request');
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
        $inboxPath = "/users/{$profile->username}/inbox";
        list($verified, $headers) = HttpSignature::verify($pkey, $signatureData, $headers, $inboxPath, $body);
        if($verified == 1) { 
            return true;
        } else {
            return false;
        }
    }

    protected function blindKeyRotation($headers, $profile, $payload)
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
        return $this->verifySignature($headers, $profile, $payload);
    }
}
