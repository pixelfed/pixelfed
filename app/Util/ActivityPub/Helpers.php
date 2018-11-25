<?php

namespace App\Util\ActivityPub;

use Cache, Purify, Storage, Request, Validator;
use App\{
    Activity,
    Follower,
    Like,
    Notification,
    Profile,
    Status
};
use Zttp\Zttp;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\File;
use Illuminate\Validation\Rule;
use App\Jobs\AvatarPipeline\CreateAvatar;
use App\Jobs\RemoteFollowPipeline\RemoteFollowImportRecent;
use App\Jobs\ImageOptimizePipeline\{ImageOptimize,ImageThumbnail};
use App\Jobs\StatusPipeline\NewStatusPipeline;
use App\Util\HttpSignatures\{GuzzleHttpSignatures, KeyStore, Context, Verifier};
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

class Helpers {

	public static function validateObject($data)
	{
		// todo: undo
		$verbs = ['Create', 'Announce', 'Like', 'Follow', 'Delete', 'Accept', 'Reject'];

		$valid = Validator::make($data, [
			'type' => [
				'required',
				Rule::in($verbs)
			],
			'id' => 'required|string',
			'actor' => 'required|string',
			'object' => 'required',
			'object.type' => 'required_if:type,Create',
			'object.attachment' => 'required_if:type,Create',
			'object.attributedTo' => 'required_if:type,Create',
			'published' => 'required_if:type,Create|date'
		])->passes();

		return $valid;
	}

	public static function verifyAttachments($data)
	{
		if(!isset($data['object']) || empty($data['object'])) {
			$data = ['object'=>$data];
		}

		$activity = $data['object'];

        $mediaTypes = ['Document', 'Image', 'Video'];
        $mimeTypes = ['image/jpeg', 'image/png', 'video/mp4'];

        if(!isset($activity['attachment']) || empty($activity['attachment'])) {
            return false;
        }

        $attachment = $activity['attachment'];
        $valid = Validator::make($attachment, [
            '*.type' => [
            	'required',
            	'string',
            	Rule::in($mediaTypes)
            ],
            '*.url' => 'required|max:255',
            '*.mediaType'  => [
            	'required',
            	'string',
            	Rule::in($mimeTypes)
            ],
            '*.name' => 'nullable|string|max:255'
        ])->passes();

        return $valid;
	}

	public static function normalizeAudience($data, $localOnly = true)
	{
		if(!isset($data['to'])) {
			return;
		}
		
		$audience = [];
		$audience['to'] = [];
		$audience['cc'] = [];
		$scope = 'private';

		if(is_array($data['to']) && !empty($data['to'])) {
			foreach ($data['to'] as $to) {
				if($to == 'https://www.w3.org/ns/activitystreams#Public') {
					$scope = 'public';
					continue;
				}
				$url = $localOnly ? self::validateLocalUrl($to) : self::validateUrl($to);
				if($url != false) {
					array_push($audience['to'], $url);
				}
			}
		}

		if(is_array($data['cc']) && !empty($data['cc'])) {
			foreach ($data['cc'] as $cc) {
				if($cc == 'https://www.w3.org/ns/activitystreams#Public') {
					$scope = 'unlisted';
					continue;
				}
				$url = $localOnly ? self::validateLocalUrl($cc) : self::validateUrl($cc);
				if($url != false) {
					array_push($audience['cc'], $url);
				}
			}
		}
		$audience['scope'] = $scope;
		return $audience;
	}

	public static function userInAudience($profile, $data)
	{
		$audience = self::normalizeAudience($data);
		$url = $profile->permalink();
		return in_array($url, $audience);
	}

	public static function validateUrl($url)
	{
		$localhosts = [
	      '127.0.0.1', 'localhost', '::1'
	    ];

	    $valid = filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED|FILTER_FLAG_HOST_REQUIRED);

	    if(in_array(parse_url($valid, PHP_URL_HOST), $localhosts)) {
	    	return false;
	    }

	    return $valid;
	}

	public static function validateLocalUrl($url)
	{
		$url = self::validateUrl($url);
		if($url) {
			$domain = config('pixelfed.domain.app');
			$host = parse_url($url, PHP_URL_HOST);
			$url = $domain === $host ? $url : false;
			return $url;
		}
		return false;
	}

	public static function zttpUserAgent()
	{
		return [
          'Accept'     => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
          'User-Agent' => 'PixelFedBot - https://pixelfed.org',
        ];
	}

	public static function fetchFromUrl($url)
	{
        $res = Zttp::withHeaders(self::zttpUserAgent())->get($url);
        $res = json_decode($res->body(), true, 8);
        if(json_last_error() == JSON_ERROR_NONE) {
        	return $res;
        } else {
        	return false;
        }
	}

	public static function fetchProfileFromUrl($url)
	{
		return self::fetchFromUrl($url);
	}

	public static function statusFirstOrFetch($url, $replyTo = true)
	{
		$url = self::validateUrl($url);
		if($url == false) {
			return;
		}

		$host = parse_url($url, PHP_URL_HOST);
		$local = config('pixelfed.domain.app') == $host ? true : false;

		if($local) {
			$id = (int) last(explode('/', $url));
			return Status::findOrFail($id);
		} else {
			$cached = Status::whereUrl($url)->first();
			if($cached) {
				return $cached;
			}
			$res = self::fetchFromUrl($url);
			if(!$res || empty($res)) {
				return;
			}

			if(isset($res['object'])) {
				$activity = $res;
			} else {
				$activity = ['object' => $res];
			}

			$profile = self::profileFirstOrNew($activity['object']['attributedTo']);
			if(isset($activity['object']['inReplyTo']) && !empty($activity['object']['inReplyTo']) && $replyTo == true) {
				$reply_to = self::statusFirstOrFetch($activity['object']['inReplyTo'], false);
				$reply_to = $reply_to->id;
			} else {
				$reply_to = null;
			}

			$status = new Status;
			$status->profile_id = $profile->id;
			$status->url = $url;
			$status->caption = strip_tags($res['content']);
			$status->rendered = Purify::clean($res['content']);
			$status->created_at = Carbon::parse($res['published']);
			$status->in_reply_to_id = $reply_to;
			$status->local = false;
			$status->save();

			self::importNoteAttachment($res, $status);

			return $status;
		}
	}

	public static function importNoteAttachment($data, Status $status)
	{
		if(self::verifyAttachments($data) == false) {
			return;
		}
		$attachments = isset($data['object']) ? $data['object']['attachment'] : $data['attachment'];
		$user = $status->profile;
		$monthHash = hash('sha1', date('Y').date('m'));
		$userHash = hash('sha1', $user->id.(string) $user->created_at);
        $storagePath = "public/m/{$monthHash}/{$userHash}";
        $allowed = explode(',', config('pixelfed.media_types'));
		foreach($attachments as $media) {
			$type = $media['mediaType'];
			$url = $media['url'];
			$valid = self::validateUrl($url);
			if(in_array($type, $allowed) == false || $valid == false) {
				continue;
			}
            $info = pathinfo($url);

            // pleroma attachment fix
            $url = str_replace(' ', '%20', $url);

            $img = file_get_contents($url, false, stream_context_create(['ssl' => ["verify_peer"=>false,"verify_peer_name"=>false]]));
            $file = '/tmp/'.str_random(16).$info['basename'];
            file_put_contents($file, $img);
            $fdata = new File($file);
            $path = Storage::putFile($storagePath, $fdata, 'public');
            $media = new Media();
            $media->status_id = $status->id;
            $media->profile_id = $status->profile_id;
            $media->user_id = null;
            $media->media_path = $path;
            $media->size = $fdata->getSize();
            $media->mime = $fdata->getMimeType();
            $media->save();

            ImageThumbnail::dispatch($media);
            ImageOptimize::dispatch($media);
            unlink($file);
		}
		return;
	}

	public static function profileFirstOrNew($url, $runJobs = false)
	{
 		$res = self::fetchProfileFromUrl($url);
 		$domain = parse_url($res['url'], PHP_URL_HOST);
        $username = $res['preferredUsername'];
        $remoteUsername = "@{$username}@{$domain}";

		$profile = Profile::whereRemoteUrl($res['url'])->first();
		if(!$profile) {
	        $profile = new Profile;
	        $profile->domain = $domain;
	        $profile->username = $remoteUsername;
	        $profile->name = strip_tags($res['name']);
	        $profile->bio = Purify::clean($res['summary']);
	        $profile->sharedInbox = isset($res['endpoints']) && isset($res['endpoints']['sharedInbox']) ? $res['endpoints']['sharedInbox'] : null;
	        $profile->inbox_url = $res['inbox'];
	        $profile->outbox_url = $res['outbox'];
	        $profile->remote_url = $res['url'];
	        $profile->public_key = $res['publicKey']['publicKeyPem'];
	        $profile->key_id = $res['publicKey']['id'];
	        $profile->save();
	        if($runJobs == true) {
				RemoteFollowImportRecent::dispatch($res, $profile);
				CreateAvatar::dispatch($profile);
	        }
		}
		return $profile;
	}

    public static function sendSignedObject($senderProfile, $url, $body)
    {
        $profile = $senderProfile;
        $keyId = $profile->keyId();

        $date = new \DateTime('UTC');
        $date = $date->format('D, d M Y H:i:s \G\M\T');
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        $headers = [
            'date'         => $date,
            'host'		   => $host,
            'content-type' => 'application/activity+json',
        ];

        $context = new Context([
            'keys' => [$profile->keyId() => $profile->private_key],
            'algorithm' => 'rsa-sha256',
            'headers' => ['(request-target)', 'date', 'host', 'content-type'],
        ]);

        $handlerStack = GuzzleHttpSignatures::defaultHandlerFromContext($context);
        $client = new Client(['handler' => $handlerStack]);

        $response = $client->request('POST', $url, ['headers' => $headers, 'json' => $body]);
        return;
    }

    private static function _headersToSigningString($headers) {
    	return implode("\n", array_map(function($k, $v){
    		return strtolower($k).': '.$v;
    	}, array_keys($headers), $headers));
    }

    public static function validateSignature($request, $payload = null)
    {
        $date = Carbon::parse($request['date']);
        $min = Carbon::now()->subHours(13);
        $max = Carbon::now()->addHours(13);

        if($date->gt($min) == false || $date->lt($max) == false) {
        	return false;
        }
        $json = json_encode($payload);
        $digest = base64_encode(hash('sha256', $json, true));
	    $parts = explode(',', $request['signature']);
	    $signatureData = [];
	    foreach($parts as $part) {
	      if(preg_match('/(.+)="(.+)"/', $part, $match)) {
	        $signatureData[$match[1]] = $match[2];
	      }
	    }

        $actor = $payload['actor'];
        $profile = self::profileFirstOrNew($actor, true);
        if(!$profile) {
        	return false;
        }
        $publicKey = $profile->public_key;
	    $path = $request['path'];
	    $host = $request['host'];
        $signingString = "(request-target): post {$path}".PHP_EOL.
        "host: {$host}".PHP_EOL.
        "date: {$request['date']}".PHP_EOL.
        "digest: {$request['digest']}".PHP_EOL.
        "content-type: {$request['contentType']}";
		$verified = openssl_verify($signingString, base64_decode($signatureData['signature']), $publicKey, OPENSSL_ALGO_SHA256);
		return (bool) $verified;
    }

    public static function fetchPublicKey()
    {
        $profile = $this->profile;
        $is_url = $this->is_url;
        $valid = $this->validateUrl();
        if (!$valid) {
            throw new \Exception('Invalid URL provided');
        }
        if ($is_url && isset($profile->public_key) && $profile->public_key) {
            return $profile->public_key;
        }

        try {
            $url = $this->profile;
            $res = Zttp::timeout(30)->withHeaders([
              'Accept'     => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
              'User-Agent' => 'PixelFedBot v0.1 - https://pixelfed.org',
            ])->get($url);
            $actor = json_decode($res->getBody(), true);
        } catch (Exception $e) {
            throw new Exception('Unable to fetch public key');
        }
        if($actor['publicKey']['owner'] != $profile) {
            throw new Exception('Invalid key match');
        }
        $this->public_key = $actor['publicKey']['publicKeyPem'];
        $this->key_id = $actor['publicKey']['id'];
        return $this;
    }
}