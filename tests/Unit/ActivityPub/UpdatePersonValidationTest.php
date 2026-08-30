<?php

namespace Tests\Unit\ActivityPub;

use App\Util\ActivityPub\Validator\UpdatePersonValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UpdatePersonValidationTest extends TestCase
{
    public $activity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activity = json_decode('{"type":"Update","object":{"url":"http://mastodon.example.org/@gargron","type":"Person","summary":"<p>Some bio</p>","publicKey":{"publicKeyPem":"-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA0gs3VnQf6am3R+CeBV4H\nlfI1HZTNRIBHgvFszRZkCERbRgEWMu+P+I6/7GJC5H5jhVQ60z4MmXcyHOGmYMK/\n5XyuHQz7V2Ssu1AxLfRN5Biq1ayb0+DT/E7QxNXDJPqSTnstZ6C7zKH/uAETqg3l\nBonjCQWyds+IYbQYxf5Sp3yhvQ80lMwHML3DaNCMlXWLoOnrOX5/yK5+dedesg2\n/HIvGk+HEt36vm6hoH7bwPuEkgA++ACqwjXRe5Mta7i3eilHxFaF8XIrJFARV0t\nqOu4GID/jG6oA+swIWndGrtR2QRJIt9QIBFfK3HG5M0koZbY1eTqwNFRHFL3xaD\nUQIDAQAB\n-----END PUBLIC KEY-----\n","owner":"http://mastodon.example.org/users/gargron","id":"http://mastodon.example.org/users/gargron#main-key"},"preferredUsername":"gargron","outbox":"http://mastodon.example.org/users/gargron/outbox","name":"gargle","manuallyApprovesFollowers":false,"inbox":"http://mastodon.example.org/users/gargron/inbox","id":"http://mastodon.example.org/users/gargron","following":"http://mastodon.example.org/users/gargron/following","followers":"http://mastodon.example.org/users/gargron/followers","endpoints":{"sharedInbox":"http://mastodon.example.org/inbox"},"attachment":[{"type":"PropertyValue","name":"foo","value":"updated"},{"type":"PropertyValue","name":"foo1","value":"updated"}],"icon":{"type":"Image","mediaType":"image/jpeg","url":"https://cd.niu.moe/accounts/avatars/000/033/323/original/fd7f8ae0b3ffedc9.jpeg"},"image":{"type":"Image","mediaType":"image/png","url":"https://cd.niu.moe/accounts/headers/000/033/323/original/850b3448fa5fd477.png"}},"id":"http://mastodon.example.org/users/gargron#updates/1519563538","actor":"http://mastodon.example.org/users/gargron","@context":["https://www.w3.org/ns/activitystreams","https://w3id.org/security/v1",{"toot":"http://joinmastodon.org/ns#","sensitive":"as:sensitive","ostatus":"http://ostatus.org#","movedTo":"as:movedTo","manuallyApprovesFollowers":"as:manuallyApprovesFollowers","inReplyToAtomUri":"ostatus:inReplyToAtomUri","conversation":"ostatus:conversation","atomUri":"ostatus:atomUri","Hashtag":"as:Hashtag","Emoji":"toot:Emoji"}]}', true);
    }

    #[Test]
    public function schema_test()
    {
        $this->assertTrue(UpdatePersonValidator::validate($this->activity));
    }

    #[Test]
    public function invalid_context()
    {
        $activity = $this->activity;
        unset($activity['@context']);
        $activity['@@context'] = 'https://www.w3.org/ns/activitystreams';
        $this->assertFalse(UpdatePersonValidator::validate($activity));
    }

    #[Test]
    public function missing_context()
    {
        $activity = $this->activity;
        unset($activity['@context']);
        $this->assertFalse(UpdatePersonValidator::validate($activity));
    }

    #[Test]
    public function missing_id()
    {
        $activity = $this->activity;
        unset($activity['id']);
        $this->assertFalse(UpdatePersonValidator::validate($activity));
    }

    #[Test]
    public function missing_type()
    {
        $activity = $this->activity;
        unset($activity['type']);
        $this->assertFalse(UpdatePersonValidator::validate($activity));
    }

    #[Test]
    public function invalid_type()
    {
        $activity = $this->activity;
        $activity['type'] = 'Create';
        $this->assertFalse(UpdatePersonValidator::validate($activity));
    }

    #[Test]
    public function invalid_object_type()
    {
        $activity = $this->activity;
        $activity['object']['type'] = 'Note';
        $this->assertFalse(UpdatePersonValidator::validate($activity));
    }

    #[Test]
    public function invalid_actor_matching_object_id()
    {
        $activity = $this->activity;
        $activity['object']['id'] = 'https://example.org/@user';
        $this->assertFalse(UpdatePersonValidator::validate($activity));
    }

    #[Test]
    public function invalid_actor_url_matching_object_id()
    {
        $activity = $this->activity;
        $activity['object']['id'] = $activity['object']['id'].'test';
        $this->assertFalse(UpdatePersonValidator::validate($activity));
    }

    #[Test]
    public function missing_actor_public_key()
    {
        $activity = $this->activity;
        unset($activity['object']['publicKey']);
        $this->assertFalse(UpdatePersonValidator::validate($activity));
    }

    #[Test]
    public function invalid_actor_public_key()
    {
        $activity = $this->activity;
        $activity['object']['publicKey'] = null;
        $this->assertFalse(UpdatePersonValidator::validate($activity));
    }

    #[Test]
    public function invalid_actor_public_key_id()
    {
        $activity = $this->activity;
        $activity['object']['publicKey']['id'] = null;
        $this->assertFalse(UpdatePersonValidator::validate($activity));
    }

    #[Test]
    public function invalid_actor_public_key_id_host()
    {
        $activity = $this->activity;
        $activity['object']['publicKey']['id'] = 'https://example.org/test';
        $this->assertFalse(UpdatePersonValidator::validate($activity));
    }

    #[Test]
    public function invalid_actor_avatar()
    {
        $activity = $this->activity;
        $activity['object']['icon']['type'] = 'TikTok';
        $this->assertFalse(UpdatePersonValidator::validate($activity));
    }

    #[Test]
    public function invalid_actor_avatar_media_type()
    {
        $activity = $this->activity;
        $activity['object']['icon']['mediaType'] = 'video/mp4';
        $this->assertFalse(UpdatePersonValidator::validate($activity));
    }

    #[Test]
    public function valid_actor_avatar_media_type_png()
    {
        $activity = $this->activity;
        $activity['object']['icon']['mediaType'] = 'image/png';
        $this->assertTrue(UpdatePersonValidator::validate($activity));
    }

    #[Test]
    public function valid_actor_avatar_media_type_jpeg()
    {
        $activity = $this->activity;
        $activity['object']['icon']['mediaType'] = 'image/jpeg';
        $this->assertTrue(UpdatePersonValidator::validate($activity));
    }

    #[Test]
    public function valid_actor_avatar_media_url()
    {
        $activity = $this->activity;
        $activity['object']['icon']['url'] = 'http://example.org/avatar.png';
        $this->assertTrue(UpdatePersonValidator::validate($activity));
    }
}
