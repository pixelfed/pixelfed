<?php

namespace App\Services;

use App\Profile;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;

class ActivityPubDeliveryService
{

	public $sender;
	public $to;
	public $payload;

	public static function queue()
	{
		return new self;
	}

	public function from($profile)
	{
		$this->sender = $profile;
		return $this;
	}

	public function to(string $url)
	{
		$this->to = $url;
		return $this;
	}

	public function payload($payload)
	{
		$this->payload = $payload;
		return $this;
	}

	public function send()
	{
		return $this->queueDelivery();
	}

	protected function queueDelivery()
	{
		abort_if(!$this->sender || !$this->to || !$this->payload, 400);
		abort_if(!Helpers::validateUrl($this->to), 400);
		abort_if($this->sender->domain != null || $this->sender->status != null, 400);

		$body = $this->payload;
		$payload = json_encode($body);
		$headers = HttpSignature::sign($this->sender, $this->to, $body);

		$ch = curl_init($this->to);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_exec($ch);
	}

}