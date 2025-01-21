<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InstanceActor;
use Cache;

class InstanceActorController extends Controller
{
	public function profile()
	{
		$res = Cache::rememberForever(InstanceActor::PROFILE_KEY, function() {
			$res = (new InstanceActor())->first()->getActor();
			return json_encode($res, JSON_UNESCAPED_SLASHES);
		});
		return response($res)->header('Content-Type', 'application/activity+json');
	}

	public function inbox()
	{
		return;
	}

	public function outbox()
	{
		$res = json_encode([
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
			'id' => config('app.url') . '/i/actor/outbox',
			'type' => 'OrderedCollection',
			'totalItems' => 0,
			'first' => config('app.url') . '/i/actor/outbox?page=true',
			'last' =>  config('app.url') . '/i/actor/outbox?min_id=0page=true'
		], JSON_UNESCAPED_SLASHES);
		return response($res)->header('Content-Type', 'application/activity+json');
	}
}
