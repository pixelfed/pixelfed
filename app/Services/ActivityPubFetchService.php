<?php

namespace App\Services;

use Zttp\Zttp;
use App\Profile;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;

class ActivityPubFetchService
{
	public $signed = true;
	public $actor;
	public $url;
	public $headers = [
		'Accept'		=> 'application/activity+json, application/json',
		'User-Agent'	=> '(Pixelfed/'.config('pixelfed.version').'; +'.config('app.url').')'
	];

	public static function queue()
	{
		return new self;
	}

	public function signed($signed = true)
	{
		$this->signed = $signed;
		return $this;
	}

	public function actor($profile)
	{
		$this->actor = $profile;
		return $this;
	}

	public function url($url)
	{
		if(!Helpers::validateUrl($url)) {
			throw new \Exception('Invalid URL');
		}
		$this->url = $url;
		return $this;
	}

	public function get()
	{
		if($this->signed == true && $this->actor == null) {
			throw new \Exception('Cannot sign request without actor');
		}
		return $this->signedRequest();
	}

	protected function signedRequest()
	{
		$this->headers = HttpSignature::sign($this->actor, $this->url, false, $this->headers);
		return Zttp::withHeaders($this->headers)->get($this->url)->body();
	}
}