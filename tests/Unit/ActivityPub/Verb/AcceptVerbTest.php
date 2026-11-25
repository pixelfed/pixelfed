<?php

namespace Tests\Unit\ActivityPub\Verb;

use App\Util\ActivityPub\Validator\Accept;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AcceptVerbTest extends TestCase
{
    protected array $validAccept;

    protected array $invalidAccept;

    protected array $mastodonAccept;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validAccept = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => 'https://example.org/og/b3e4a40b-0b26-4c5a-9079-094bd633fab7',
            'type' => 'Accept',
            'actor' => 'https://example.org/u/alice',
            'object' => [
                'id' => 'https://example.net/u/bob#follows/bb27f601-ddb9-4567-8f16-023d90605ca9',
                'type' => 'Follow',
                'actor' => 'https://example.net/u/bob',
                'object' => 'https://example.org/u/alice',
            ],
        ];

        $this->invalidAccept = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => 'https://example.org/og/b3e4a40b-0b26-4c5a-9079-094bd633fab7',
            'type' => 'Accept2',
            'actor' => 'https://example.org/u/alice',
            'object' => [
                'id' => 'https://example.net/u/bob#follows/bb27f601-ddb9-4567-8f16-023d90605ca9',
                'type' => 'Follow',
                'actor' => 'https://example.net/u/bob',
                'object' => 'https://example.org/u/alice',
            ],
        ];

        $this->mastodonAccept = [
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
            'type' => 'Accept',
            'object' => [
                'type' => 'Follow',
                'object' => 'https://mastodon.example.org/users/admin',
                'id' => 'https://pixelfed.dev/users/dsup#follows/4',
                'actor' => 'https://pixelfed.dev/users/dsup',
            ],
            'nickname' => 'dsup',
            'id' => 'https://mastodon.example.org/users/admin#accepts/follows/4',
            'actor' => 'https://mastodon.example.org/users/admin',
            'signature' => [
                'type' => 'RsaSignature2017',
                'signatureValue' => 'rBzK4Kqhd4g7HDS8WE5oRbWQb2R+HF/6awbUuMWhgru/xCODT0SJWSri0qWqEO4fPcpoUyz2d25cw6o+iy9wiozQb3hQNnu69AR+H5Mytc06+g10KCHexbGhbAEAw/7IzmeXELHUbaqeduaDIbdt1zw4RkwLXdqgQcGXTJ6ND1wM3WMHXQCK1m0flasIXFoBxpliPAGiElV8s0+Ltuh562GvflG3kB3WO+j+NaR0ZfG5G9N88xMj9UQlCKit5gpAE5p6syUsCU2WGBHywTumv73i3OVTIFfq+P9AdMsRuzw1r7zoKEsthW4aOzLQDi01ZjvdBz8zH6JnjDU7SMN/Ig==',
                'creator' => 'https://mastodon.example.org/users/admin#main-key',
                'created' => '2018-02-17T14:36:41Z',
            ],
        ];
    }

    #[Test]
    public function basic_accept()
    {
        $this->assertTrue(Accept::validate($this->validAccept));
    }

    #[Test]
    public function invalid_accept()
    {
        $this->assertFalse(Accept::validate($this->invalidAccept));
    }

    #[Test]
    public function mastodon_accept()
    {
        $this->assertTrue(Accept::validate($this->mastodonAccept));
    }

    #[Test]
    public function accept_with_follow_id_reference()
    {
        $payload = $this->validAccept;
        $payload['object'] = 'https://example.net/u/bob#follows/1030577900215394306';

        $this->assertTrue(Accept::validate($payload));
    }

    #[Test]
    public function accept_with_embedded_follow_id_only()
    {
        $payload = $this->validAccept;
        $payload['object'] = [
            'id' => 'https://example.net/u/bob#follows/1030577900215394306',
            'type' => 'Follow',
        ];

        $this->assertTrue(Accept::validate($payload));
    }

    #[Test]
    public function accept_with_non_url_object()
    {
        $payload = $this->validAccept;
        $payload['object'] = 'not a url';

        $this->assertFalse(Accept::validate($payload));
    }

    #[Test]
    public function accept_with_embedded_reference_to_non_follow()
    {
        $payload = $this->validAccept;
        $payload['object'] = [
            'id' => 'https://example.net/u/bob#likes/1',
            'type' => 'Like',
        ];

        $this->assertFalse(Accept::validate($payload));
    }

    #[Test]
    public function accept_with_embedded_follow_of_someone_else()
    {
        $payload = $this->validAccept;
        $payload['object']['object'] = 'https://example.org/u/carol';

        $this->assertFalse(Accept::validate($payload));
    }
}
