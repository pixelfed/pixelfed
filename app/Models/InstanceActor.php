<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstanceActor extends Model
{
	use HasFactory;

	const PROFILE_BASE = '/i/actor';
	const KEY_ID = '/i/actor#main-key';
	const PROFILE_KEY = 'federation:_v3:instance:actor:profile';
	const PKI_PUBLIC = 'federation:_v1:instance:actor:profile:pki_public';
	const PKI_PRIVATE = 'federation:_v1:instance:actor:profile:pki_private';

	public function permalink($suffix = '')
	{
		return url(self::PROFILE_BASE . $suffix);
	}

	public function getActor()
	{
		return [
			'@context' => 'https://www.w3.org/ns/activitystreams',
			'id' => $this->permalink(),
			'type' => 'Application',
			'inbox' => $this->permalink('/inbox'),
			'outbox' => $this->permalink('/outbox'),
			'preferredUsername' => config('pixelfed.domain.app'),
			'publicKey' => [
				'id' => $this->permalink('#main-key'),
				'owner' => $this->permalink(),
				'publicKeyPem' => $this->public_key
			],
			'manuallyApprovesFollowers' => true,
			'url' => url('/site/kb/instance-actor')
		];
	}
}
