<?php

namespace App\Util\ActivityPub;

use App\Services\ActivityPubFetchService;

class DiscoverActor
{
    protected $url;

    protected $response;

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function fetch()
    {
        // SSRF-hardened: route through the validated, IP-pinned,
        // redirect-revalidating ActivityPub fetch path instead of a raw
        // Http::get on an unvalidated URL.
        $this->response = ActivityPubFetchService::get($this->url) ?: null;

        return $this;
    }

    public function getResponse()
    {
        if (! $this->response) {
            return null;
        }

        return json_decode($this->response, true);
    }

    public function getJsonResponse()
    {
        return $this->response;
    }

    public function discover()
    {
        $this->fetch();
        $res = $this->getResponse();

        if (empty($res) || ! in_array('type', $res) || $res['type'] !== 'Person') {
            throw new \Exception('Invalid Actor Object');
        }

        return $res;
    }
}
