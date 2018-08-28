<?php

namespace App\Util\ActivityPub\Concern;

use Zttp\Zttp;

class HTTPSignature
{
    protected $localhosts = [
      '127.0.0.1', 'localhost', '::1',
    ];
    public $profile;
    public $is_url;

    public function validateUrl()
    {
        // If the profile exists, assume its valid
        if ($this->is_url === false) {
            return true;
        }

        $url = $this->profile;

        try {
            $url = filter_var($url, FILTER_VALIDATE_URL);
            $parsed = parse_url($url, PHP_URL_HOST);
            if (!$parsed || in_array($parsed, $this->localhosts)) {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function fetchPublicKey($profile, bool $is_url = true)
    {
        $this->profile = $profile;
        $this->is_url = $is_url;
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

        return $actor['publicKey']['publicKeyPem'];
    }

    public function sendSignedObject($senderProfile, $url, $body)
    {
        $profile = $senderProfile;
        $context = new Context([
            'keys'      => [$profile->keyId() => $profile->private_key],
            'algorithm' => 'rsa-sha256',
            'headers'   => ['(request-target)', 'Date'],
        ]);

        $handlerStack = GuzzleHttpSignatures::defaultHandlerFromContext($context);
        $client = new Client(['handler' => $handlerStack]);

        $headers = [
            'Accept'       => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
            'Date'         => date('D, d M Y h:i:s').' GMT',
            'Content-Type' => 'application/activity+json',
            'User-Agent'   => 'PixelFedBot - https://pixelfed.org',
        ];

        $response = $client->post($url, [
            'options' => [
                'allow_redirects' => false,
                'verify'          => true,
                'timeout'         => 30,
            ],
            'headers' => $headers,
            'body'    => $body,
        ]);

        return $response->getBody();
    }
}
