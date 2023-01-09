<?php

namespace Tests\Unit\ActivityPub\Verb;

use App\Util\ActivityPub\Validator\Like;
use Tests\TestCase;

class LikeTest extends TestCase
{
    protected array $basicLike;

    public function setUp(): void
    {
        parent::setUp();

        $this->basicLike = [
            'type' => 'Like',
            'signature' => [
                'type' => 'RsaSignature2017',
                'signatureValue' => 'fdxMfQSMwbC6wP6sh6neS/vM5879K67yQkHTbiT5Npr5wAac0y6+o3Ij+41tN3rL6wfuGTosSBTHOtta6R4GCOOhCaCSLMZKypnp1VltCzLDoyrZELnYQIC8gpUXVmIycZbREk22qWUe/w7DAFaKK4UscBlHDzeDVcA0K3Se5Sluqi9/Zh+ldAnEzj/rSEPDjrtvf5wGNf3fHxbKSRKFt90JvKK6hS+vxKUhlRFDf6/SMETw+EhwJSNW4d10yMUakqUWsFv4Acq5LW7l+HpYMvlYY1FZhNde1+uonnCyuQDyvzkff8zwtEJmAXC4RivO/VVLa17SmqheJZfI8oluVg==',
                'creator' => 'http://mastodon.example.org/users/admin#main-key',
                'created' => '2018-02-17T18:57:49Z',
            ],
            'object' => 'http://pixelfed.dev/p/1',
            'nickname' => 'dsup',
            'id' => 'http://mastodon.example.org/users/admin#likes/2',
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
    public function basic_like()
    {
        $this->assertTrue(Like::validate($this->basicLike));
    }
}
