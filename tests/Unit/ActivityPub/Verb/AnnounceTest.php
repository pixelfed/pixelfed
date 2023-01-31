<?php

namespace Tests\Unit\ActivityPub\Verb;

use App\Util\ActivityPub\Validator\Announce;
use Tests\TestCase;

class AnnounceTest extends TestCase
{
    protected array $validAnnounce;
    protected array $invalidAnnounce;
    protected array $invalidDate;
    protected array $contextMissing;
    protected array $audienceMissing;
    protected array $audienceMissing2;
    protected array $invalidActor;
    protected array $invalidActor2;
    protected array $mastodonAnnounce;

    public function setUp(): void
    {
        parent::setUp();

        $this->validAnnounce = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => 'https://example.org/users/alice/statuses/100000000000001/activity',
            'type' => 'Announce',
            'actor' => 'https://example.org/users/alice',
            'published' => '2018-12-31T23:59:59Z',
            'to' => [
                'https://www.w3.org/ns/activitystreams#Public'
            ],
            'cc' => [
                'https://example.org/users/bob',
                'https://example.org/users/alice/followers'
            ],
            'object' => 'https://example.org/p/bob/100000000000000',
        ];

        $this->invalidAnnounce = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => 'https://example.org/users/alice/statuses/100000000000001/activity',
            'type' => 'Announce2',
            'actor' => 'https://example.org/users/alice',
            'published' => '2018-12-31T23:59:59Z',
            'to' => [
                'https://www.w3.org/ns/activitystreams#Public'
            ],
            'cc' => [
                'https://example.org/users/bob',
                'https://example.org/users/alice/followers'
            ],
            'object' => 'https://example.org/p/bob/100000000000000',
        ];

        $this->contextMissing = [
            'id' => 'https://example.org/users/alice/statuses/100000000000001/activity',
            'type' => 'Announce',
            'actor' => 'https://example.org/users/alice',
            'published' => '2018-12-31T23:59:59Z',
            'to' => [
                'https://www.w3.org/ns/activitystreams#Public'
            ],
            'cc' => [
                'https://example.org/users/bob',
                'https://example.org/users/alice/followers'
            ],
            'object' => 'https://example.org/p/bob/100000000000000',
        ];

        $this->invalidActor = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => 'https://example.org/users/alice/statuses/100000000000001/activity',
            'type' => 'Announce',
            'actor' => '10000',
            'published' => '2018-12-31T23:59:59Z',
            'to' => [
                'https://www.w3.org/ns/activitystreams#Public'
            ],
            'cc' => [
                'https://example.org/users/bob',
                'https://example.org/users/alice/followers'
            ],
            'object' => 'https://example.org/p/bob/100000000000000',
        ];

        $this->invalidActor2 = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => 'https://example.org/users/alice/statuses/100000000000001/activity',
            'type' => 'Announce',
            'published' => '2018-12-31T23:59:59Z',
            'to' => [
                'https://www.w3.org/ns/activitystreams#Public'
            ],
            'cc' => [
                'https://example.org/users/bob',
                'https://example.org/users/alice/followers'
            ],
            'object' => 'https://example.org/p/bob/100000000000000',
        ];

        $this->mastodonAnnounce = [
            'type' => 'Announce',
            'to' => [
                'https://www.w3.org/ns/activitystreams#Public',
            ],
            'signature' => [
                'type' => 'RsaSignature2017',
                'signatureValue' => 'T95DRE0eAligvMuRMkQA01lsoz2PKi4XXF+cyZ0BqbrO12p751TEWTyyRn5a+HH0e4kc77EUhQVXwMq80WAYDzHKVUTf2XBJPBa68vl0j6RXw3+HK4ef5hR4KWFNBU34yePS7S1fEmc1mTG4Yx926wtmZwDpEMTp1CXOeVEjCYzmdyHpepPPH2ZZettiacmPRSqBLPGWZoot7kH/SioIdnrMGY0I7b+rqkIdnnEcdhu9N1BKPEO9Sr+KmxgAUiidmNZlbBXX6gCxp8BiIdH4ABsIcwoDcGNkM5EmWunGW31LVjsEQXhH5c1Wly0ugYYPCg/0eHLNBOhKkY/teSM8Lg==',
                'creator' => 'https://mastodon.example.org/users/admin#main-key',
                'created' => '2018-02-17T19:39:15Z',
            ],
            'published' => '2018-02-17T19:39:15Z',
            'object' => 'https://mastodon.example.org/@admin/99541947525187367',
            'id' => 'https://mastodon.example.org/users/admin/statuses/99542391527669785/activity',
            'cc' => [
                'https://mastodon.example.org/users/admin',
                'https://mastodon.example.org/users/admin/followers',
            ],
            'atomUri' => 'https://mastodon.example.org/users/admin/statuses/99542391527669785/activity',
            'actor' => 'https://mastodon.example.org/users/admin',
            '@context' => [
                'https://www.w3.org/ns/activitystreams',
                'https://w3id.org/security/v1',
                [
                    'toot' => 'https://joinmastodon.org/ns#',
                    'sensitive' => 'as:sensitive',
                    'ostatus' => 'https://ostatus.org#',
                    'movedTo' => 'as:movedTo',
                    'manuallyApprovesFollowers' => 'as:manuallyApprovesFollowers',
                    'inReplyToAtomUri' => 'ostatus:inReplyToAtomUri',
                    'conversation' => 'ostatus:conversation',
                    'atomUri' => 'ostatus:atomUri',
                    'Hashtag' => 'as:Hashtag',
                    'Emoji' => 'toot:Emoji',
                ],
            ],
        ];
    }

    /** @test */
    public function basic_accept()
    {
        $this->assertTrue(Announce::validate($this->validAnnounce));
    }

    /** @test */
    public function invalid_accept()
    {
        $this->assertFalse(Announce::validate($this->invalidAnnounce));
    }

    /** @test */
    public function context_missing()
    {
        $this->assertFalse(Announce::validate($this->contextMissing));
    }

    /** @test */
    public function invalid_actor()
    {
        $this->assertFalse(Announce::validate($this->invalidActor));
        $this->assertFalse(Announce::validate($this->invalidActor2));
    }

    /** @test */
    public function mastodon_announce()
    {
        $this->assertTrue(Announce::validate($this->mastodonAnnounce));
    }
}
