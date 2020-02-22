<?php

namespace Tests\Unit\ActivityPub\Verb;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Util\ActivityPub\Validator\Accept;

class AcceptVerbTest extends TestCase
{
    protected $validAccept;
    protected $invalidAccept;

    public function setUp(): void
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
                'object' => 'https://example.org/u/alice'
            ]
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
                'object' => 'https://example.org/u/alice'
            ]
        ];
    }

    /** @test */
    public function basic_accept()
    {
        $this->assertTrue(Accept::validate($this->validAccept));
    }

    /** @test */
    public function invalid_accept()
    {
        $this->assertFalse(Accept::validate($this->invalidAccept));
    }
}
