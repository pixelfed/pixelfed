<?php

namespace App\Util\ActivityPub;

use Exception;
use Zttp\Zttp;

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
        $res = Zttp::withHeaders([
      'Accept'     => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
      'User-Agent' => 'PixelfedBot - https://pixelfed.org',
    ])->get($this->url);
        $this->response = $res->body();

        return $this;
    }

    public function getResponse()
    {
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

        if (empty($res) || !in_array('type', $res) || $res['type'] !== 'Person') {
            throw new Exception('Invalid Actor Object');
        }

        return $res;
    }
}
