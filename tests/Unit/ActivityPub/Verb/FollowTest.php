<?php

namespace Tests\Unit\ActivityPub\Verb;

use App\Util\ActivityPub\Validator\Follow;
use Tests\TestCase;

class FollowTest extends TestCase
{
    protected array $basicFollow;

    public function setUp(): void
    {
        parent::setUp();

        $this->basicFollow = [
            'type' => 'Follow',
            'signature' => [
                'type' => 'RsaSignature2017',
                'signatureValue' => 'Kn1/UkAQGJVaXBfWLAHcnwHg8YMAUqlEaBuYLazAG+pz5hqivsyrBmPV186Xzr+B4ZLExA9+SnOoNx/GOz4hBm0kAmukNSILAsUd84tcJ2yT9zc1RKtembK4WiwOw7li0+maeDN0HaB6t+6eTqsCWmtiZpprhXD8V1GGT8yG7X24fQ9oFGn+ng7lasbcCC0988Y1eGqNe7KryxcPuQz57YkDapvtONzk8gyLTkZMV4De93MyRHq6GVjQVIgtiYabQAxrX6Q8C+4P/jQoqdWJHEe+MY5JKyNaT/hMPt2Md1ok9fZQBGHlErk22/zy8bSN19GdG09HmIysBUHRYpBLig==',
                'creator' => 'http://mastodon.example.org/users/admin#main-key',
                'created' => '2018-02-17T13:29:31Z',
            ],
            'object' => 'http://pixelfed.dev/users/dsup',
            'nickname' => 'dsup',
            'id' => 'http://mastodon.example.org/users/admin#follows/2',
            'actor' => 'http://mastodon.example.org/users/admin',
            '@context' => [
                'https://www.w3.org/ns/activitystreams',
                'https://w3id.org/security/v1',
                [
                    'toot' => 'http://joinmastodon.org/ns#',
                    'sensitive' => 'as:sensitive',
                    'ostatus' => 'http://ostatus.org#',
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
    public function basic_follow()
    {
        $this->assertTrue(Follow::validate($this->basicFollow));
    }
}
