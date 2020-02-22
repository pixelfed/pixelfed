<?php

namespace Tests\Unit\ActivityPub;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Util\ActivityPub\Helpers;

class AudienceScopeTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->invalid = [
            'id' => 'test',
            'type' => 'Announce',
            'actor' => null,
            'published' => '',
            'to' => ['test'],
            'cc' => 'test',
            'object' => 'test'
        ];

        $this->mastodon = json_decode('{"@context":["https://www.w3.org/ns/activitystreams","https://w3id.org/security/v1",{"manuallyApprovesFollowers":"as:manuallyApprovesFollowers","sensitive":"as:sensitive","movedTo":{"@id":"as:movedTo","@type":"@id"},"Hashtag":"as:Hashtag","ostatus":"http://ostatus.org#","atomUri":"ostatus:atomUri","inReplyToAtomUri":"ostatus:inReplyToAtomUri","conversation":"ostatus:conversation","toot":"http://joinmastodon.org/ns#","Emoji":"toot:Emoji","focalPoint":{"@container":"@list","@id":"toot:focalPoint"},"featured":{"@id":"toot:featured","@type":"@id"},"schema":"http://schema.org#","PropertyValue":"schema:PropertyValue","value":"schema:value"}],"id":"https://mastodon.social/users/dansup/statuses/100784657480587830/activity","type":"Announce","actor":"https://mastodon.social/users/dansup","published":"2018-09-25T05:03:49Z","to":["https://www.w3.org/ns/activitystreams#Public"],"cc":["https://pleroma.site/users/pixeldev","https://mastodon.social/users/dansup/followers"],"object":"https://pleroma.site/objects/68b5c876-f52b-4819-8d81-de6839d73fbc","atomUri":"https://mastodon.social/users/dansup/statuses/100784657480587830/activity"}', true);

        $this->pleroma = json_decode('{"@context":"https://www.w3.org/ns/activitystreams","actor":"https://pleroma.site/users/pixeldev","cc":["https://www.w3.org/ns/activitystreams#Public"],"context":"tag:mastodon.social,2018-10-14:objectId=59146153:objectType=Conversation","context_id":12325955,"id":"https://pleroma.site/activities/db2273eb-d504-4e3a-8f74-c343d069755a","object":"https://mastodon.social/users/dansup/statuses/100891324792793720","published":"2018-10-14T01:22:18.554227Z","to":["https://pleroma.site/users/pixeldev/followers","https://mastodon.social/users/dansup"],"type":"Announce"}', true);
    }

    public function testBasicValidation()
    {
        $this->assertFalse(Helpers::validateObject($this->invalid));
    }

    public function testMastodonValidation()
    {
        $this->assertTrue(Helpers::validateObject($this->mastodon));
    }

    public function testPleromaValidation()
    {
        $this->assertTrue(Helpers::validateObject($this->pleroma));
    }

    public function testMastodonAudienceScope()
    {
        $scope = Helpers::normalizeAudience($this->mastodon, false);
        $actual = [
             "to" => [],
             "cc" => [
               "https://pleroma.site/users/pixeldev",
               "https://mastodon.social/users/dansup/followers",
             ],
             "scope" => "public",
         ];

        $this->assertEquals($scope, $actual);
    }

    public function testPleromaAudienceScope()
    {
        $scope = Helpers::normalizeAudience($this->pleroma, false);
        $actual = [
            "to" => [
                "https://pleroma.site/users/pixeldev/followers",
                "https://mastodon.social/users/dansup",
            ],
            "cc" => [],
            "scope" => "unlisted",
        ];

        $this->assertEquals($scope, $actual);
    }

    public function testInvalidAudienceScope()
    {
        $scope = Helpers::normalizeAudience($this->invalid, false);
        $actual = [
            'to' => [],
            'cc' => [],
            'scope' => 'private'
        ];
        $this->assertEquals($scope, $actual);
    }
}
