<?php

namespace Tests\Unit\ActivityPub;

use App\Util\ActivityPub\Validator\UpdatePersonValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UpdatePersonValidationTest extends TestCase
{
	public $activity;

	public function setUp(): void
	{
		parent::setUp();

		$this->activity = json_decode('{"type":"Update","object":{"url":"http://mastodon.example.org/@gargron","type":"Person","summary":"<p>Some bio</p>","publicKey":{"publicKeyPem":"-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA0gs3VnQf6am3R+CeBV4H\nlfI1HZTNRIBHgvFszRZkCERbRgEWMu+P+I6/7GJC5H5jhVQ60z4MmXcyHOGmYMK/\n5XyuHQz7V2Ssu1AxLfRN5Biq1ayb0+DT/E7QxNXDJPqSTnstZ6C7zKH/uAETqg3l\nBonjCQWyds+IYbQYxf5Sp3yhvQ80lMwHML3DaNCMlXWLoOnrOX5/yK5+dedesg2\n/HIvGk+HEt36vm6hoH7bwPuEkgA++ACqwjXRe5Mta7i3eilHxFaF8XIrJFARV0t\nqOu4GID/jG6oA+swIWndGrtR2QRJIt9QIBFfK3HG5M0koZbY1eTqwNFRHFL3xaD\nUQIDAQAB\n-----END PUBLIC KEY-----\n","owner":"http://mastodon.example.org/users/gargron","id":"http://mastodon.example.org/users/gargron#main-key"},"preferredUsername":"gargron","outbox":"http://mastodon.example.org/users/gargron/outbox","name":"gargle","manuallyApprovesFollowers":false,"inbox":"http://mastodon.example.org/users/gargron/inbox","id":"http://mastodon.example.org/users/gargron","following":"http://mastodon.example.org/users/gargron/following","followers":"http://mastodon.example.org/users/gargron/followers","endpoints":{"sharedInbox":"http://mastodon.example.org/inbox"},"attachment":[{"type":"PropertyValue","name":"foo","value":"updated"},{"type":"PropertyValue","name":"foo1","value":"updated"}],"icon":{"type":"Image","mediaType":"image/jpeg","url":"https://cd.niu.moe/accounts/avatars/000/033/323/original/fd7f8ae0b3ffedc9.jpeg"},"image":{"type":"Image","mediaType":"image/png","url":"https://cd.niu.moe/accounts/headers/000/033/323/original/850b3448fa5fd477.png"}},"id":"http://mastodon.example.org/users/gargron#updates/1519563538","actor":"http://mastodon.example.org/users/gargron","@context":["https://www.w3.org/ns/activitystreams","https://w3id.org/security/v1",{"toot":"http://joinmastodon.org/ns#","sensitive":"as:sensitive","ostatus":"http://ostatus.org#","movedTo":"as:movedTo","manuallyApprovesFollowers":"as:manuallyApprovesFollowers","inReplyToAtomUri":"ostatus:inReplyToAtomUri","conversation":"ostatus:conversation","atomUri":"ostatus:atomUri","Hashtag":"as:Hashtag","Emoji":"toot:Emoji"}]}', true);
	}

	#[Test]
	public function schemaTest()
	{
		$this->assertTrue(UpdatePersonValidator::validate($this->activity));
	}

	#[Test]
	public function invalidContext()
	{
		$activity = $this->activity;
		unset($activity['@context']);
		$activity['@@context'] = 'https://www.w3.org/ns/activitystreams';
		$this->assertFalse(UpdatePersonValidator::validate($activity));
	}

	#[Test]
	public function missingContext()
	{
		$activity = $this->activity;
		unset($activity['@context']);
		$this->assertFalse(UpdatePersonValidator::validate($activity));
	}

	#[Test]
	public function missingId()
	{
		$activity = $this->activity;
		unset($activity['id']);
		$this->assertFalse(UpdatePersonValidator::validate($activity));
	}

	#[Test]
	public function missingType()
	{
		$activity = $this->activity;
		unset($activity['type']);
		$this->assertFalse(UpdatePersonValidator::validate($activity));
	}

	#[Test]
	public function invalidType()
	{
		$activity = $this->activity;
		$activity['type'] = 'Create';
		$this->assertFalse(UpdatePersonValidator::validate($activity));
	}

	#[Test]
	public function invalidObjectType()
	{
		$activity = $this->activity;
		$activity['object']['type'] = 'Note';
		$this->assertFalse(UpdatePersonValidator::validate($activity));
	}

	#[Test]
	public function invalidActorMatchingObjectId()
	{
		$activity = $this->activity;
		$activity['object']['id'] = 'https://example.org/@user';
		$this->assertFalse(UpdatePersonValidator::validate($activity));
	}

	#[Test]
	public function invalidActorUrlMatchingObjectId()
	{
		$activity = $this->activity;
		$activity['object']['id'] = $activity['object']['id'] . 'test';
		$this->assertFalse(UpdatePersonValidator::validate($activity));
	}

	#[Test]
	public function missingActorPublicKey()
	{
		$activity = $this->activity;
		unset($activity['object']['publicKey']);
		$this->assertFalse(UpdatePersonValidator::validate($activity));
	}

	#[Test]
	public function invalidActorPublicKey()
	{
		$activity = $this->activity;
		$activity['object']['publicKey'] = null;
		$this->assertFalse(UpdatePersonValidator::validate($activity));
	}

	#[Test]
	public function invalidActorPublicKeyId()
	{
		$activity = $this->activity;
		$activity['object']['publicKey']['id'] = null;
		$this->assertFalse(UpdatePersonValidator::validate($activity));
	}

	#[Test]
	public function invalidActorPublicKeyIdHost()
	{
		$activity = $this->activity;
		$activity['object']['publicKey']['id'] = 'https://example.org/test';
		$this->assertFalse(UpdatePersonValidator::validate($activity));
	}

	#[Test]
	public function invalidActorAvatar()
	{
		$activity = $this->activity;
		$activity['object']['icon']['type'] = 'TikTok';
		$this->assertFalse(UpdatePersonValidator::validate($activity));
	}

	#[Test]
	public function invalidActorAvatarMediaType()
	{
		$activity = $this->activity;
		$activity['object']['icon']['mediaType'] = 'video/mp4';
		$this->assertFalse(UpdatePersonValidator::validate($activity));
	}

	#[Test]
	public function validActorAvatarMediaTypePng()
	{
		$activity = $this->activity;
		$activity['object']['icon']['mediaType'] = 'image/png';
		$this->assertTrue(UpdatePersonValidator::validate($activity));
	}

	#[Test]
	public function validActorAvatarMediaTypeJpeg()
	{
		$activity = $this->activity;
		$activity['object']['icon']['mediaType'] = 'image/jpeg';
		$this->assertTrue(UpdatePersonValidator::validate($activity));
	}

	#[Test]
	public function validActorAvatarMediaUrl()
	{
		$activity = $this->activity;
		$activity['object']['icon']['url'] = 'http://example.org/avatar.png';
		$this->assertTrue(UpdatePersonValidator::validate($activity));
	}
}
