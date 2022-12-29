<?php

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
use App\Jobs\DeletePipeline\DeleteRemoteProfilePipeline;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class DeleteWorker implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $headers;
	protected $payload;

	public $timeout = 300;
	public $tries = 1;
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
		$payload = json_decode($this->payload, true, 8);

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
			if($this->verifySignature($headers, $payload) == true) {
				$actorDelete = Profile::whereRemoteUrl($actor)->exists();
				if($actorDelete) {
					if($this->verifySignature($headers, $payload) == true) {
						Cache::set($key, false);
						$profile = Profile::whereNotNull('domain')
							->whereNull('status')
							->whereRemoteUrl($actor)
							->first();
						if($profile) {
							DeleteRemoteProfilePipeline::dispatch($profile)->onQueue('inbox');
						}
						return 1;
					} else {
						// Signature verification failed, exit.
						return 1;
					}
				} else {
					// Remote user doesn't exist, exit early.
					return 1;
				}

				return 1;
			} else {
				return 1;
			}
		} else {
			$profile = null;

			if($this->verifySignature($headers, $payload) == true) {
				ActivityHandler::dispatch($headers, $profile, $payload)->onQueue('delete');
				return 1;
			} else {
				return 1;
			}
		}

	}

	protected function verifySignature($headers, $payload)
	{
		$body = $this->payload;
		$bodyDecoded = $payload;
		$signature = is_array($headers['signature']) ? $headers['signature'][0] : $headers['signature'];
		$date = is_array($headers['date']) ? $headers['date'][0] : $headers['date'];
		if(!$signature) {
			return false;
		}
		if(!$date) {
			return false;
		}
		if(!now()->parse($date)->gt(now()->subDays(1)) ||
		   !now()->parse($date)->lt(now()->addDays(1))
	   ) {
			return false;
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
			$attr = Helpers::pluckval($bodyDecoded['object']['attributedTo']);
			if(is_array($attr)) {
				if(isset($attr['id'])) {
					$attr = $attr['id'];
				} else {
					$attr = "";
				}
			}
			if(parse_url($attr, PHP_URL_HOST) !== $keyDomain) {
				return false;
			}
		}
		if(!$keyDomain || !$idDomain || $keyDomain !== $idDomain) {
			return false;
		}
		$actor = Profile::whereKeyId($keyId)->first();
		if(!$actor) {
			$actorUrl = is_array($bodyDecoded['actor']) ? $bodyDecoded['actor'][0] : $bodyDecoded['actor'];
			$actor = Helpers::profileFirstOrNew($actorUrl);
		}
		if(!$actor) {
			return false;
		}
		$pkey = openssl_pkey_get_public($actor->public_key);
		if(!$pkey) {
			return false;
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

		try {
			$res = Http::timeout(20)->withHeaders([
			  'Accept'     => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
			  'User-Agent' => 'PixelfedBot v0.1 - https://pixelfed.org',
			])->get($actor->remote_url);
		} catch (ConnectionException $e) {
			return false;
		}

		if(!$res->ok()) {
			return false;
		}

		$res = json_decode($res->body(), true, 8);
		if(!isset($res['publicKey'], $res['publicKey']['id'])) {
			return;
		}
		if($res['publicKey']['id'] !== $actor->key_id) {
			return;
		}
		$actor->public_key = $res['publicKey']['publicKeyPem'];
		$actor->save();
		return $this->verifySignature($headers, $payload);
	}
}
