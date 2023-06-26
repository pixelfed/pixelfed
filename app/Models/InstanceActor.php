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
			"@context" => [
                "https://www.w3.org/ns/activitystreams",
                "https://w3id.org/security/v1",
                [
                    "manuallyApprovesFollowers" => "as:manuallyApprovesFollowers",
                    "toot" => "http://joinmastodon.org/ns#",
                    "featured" => [
                        "@id" => "toot:featured",
                        "@type" => "@id"
                    ],
                    "featuredTags" => [
                        "@id" => "toot:featuredTags",
                        "@type" => "@id"
                    ],
                    "alsoKnownAs" => [
                        "@id" => "as:alsoKnownAs",
                        "@type" => "@id"
                    ],
                    "movedTo" => [
                        "@id" => "as:movedTo",
                        "@type" => "@id"
                    ],
                    "schema" => "http://schema.org#",
                    "PropertyValue" => "schema:PropertyValue",
                    "value" => "schema:value",
                    "discoverable" => "toot:discoverable",
                    "Device" => "toot:Device",
                    "Ed25519Signature" => "toot:Ed25519Signature",
                    "Ed25519Key" => "toot:Ed25519Key",
                    "Curve25519Key" => "toot:Curve25519Key",
                    "EncryptedMessage" => "toot:EncryptedMessage",
                    "publicKeyBase64" => "toot:publicKeyBase64",
                    "deviceId" => "toot:deviceId",
                    "claim" => [
                        "@type" => "@id",
                        "@id" => "toot:claim"
                    ],
                    "fingerprintKey" => [
                        "@type" => "@id",
                        "@id" => "toot:fingerprintKey"
                    ],
                    "identityKey" => [
                        "@type" => "@id",
                        "@id" => "toot:identityKey"
                    ],
                    "devices" => [
                        "@type" => "@id",
                        "@id" => "toot:devices"
                    ],
                    "messageFranking" => "toot:messageFranking",
                    "messageType" => "toot:messageType",
                    "cipherText" => "toot:cipherText",
                    "suspended" => "toot:suspended"
                ]
            ],
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
