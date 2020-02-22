<?php

namespace Tests\Unit\ActivityPub\Verb;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Util\ActivityPub\Validator\UndoFollow;

class UndoFollowTest extends TestCase
{
    protected $validUndo;
    protected $invalidUndo;

    public function setUp(): void
    {
        parent::setUp();

        $this->validUndo = json_decode('{"@context":["https://www.w3.org/ns/activitystreams","https://w3id.org/security/v1",{"toot":"http://joinmastodon.org/ns#","sensitive":"as:sensitive","ostatus":"http://ostatus.org#","movedTo":"as:movedTo","manuallyApprovesFollowers":"as:manuallyApprovesFollowers","inReplyToAtomUri":"ostatus:inReplyToAtomUri","conversation":"ostatus:conversation","atomUri":"ostatus:atomUri","Hashtag":"as:Hashtag","Emoji":"toot:Emoji"}],"signature":{"type":"RsaSignature2017","signatureValue":"Kn1/UkAQGJVaXBfWLAHcnwHg8YMAUqlEaBuYLazAG+pz5hqivsyrBmPV186Xzr+B4ZLExA9+SnOoNx/GOz4hBm0kAmukNSILAsUd84tcJ2yT9zc1RKtembK4WiwOw7li0+maeDN0HaB6t+6eTqsCWmtiZpprhXD8V1GGT8yG7X24fQ9oFGn+ng7lasbcCC0988Y1eGqNe7KryxcPuQz57YkDapvtONzk8gyLTkZMV4De93MyRHq6GVjQVIgtiYabQAxrX6Q8C+4P/jQoqdWJHEe+MY5JKyNaT/hMPt2Md1ok9fZQBGHlErk22/zy8bSN19GdG09HmIysBUHRYpBLig==","creator":"http://mastodon.example.org/users/admin#main-key","created":"2018-02-17T13:29:31Z"},"type":"Undo","object":{"type":"Follow","object":"http://localtesting.pleroma.lol/users/lain","nickname":"lain","id":"http://mastodon.example.org/users/admin#follows/2","actor":"http://mastodon.example.org/users/admin"},"actor":"http://mastodon.example.org/users/admin","id":"http://mastodon.example.org/users/admin#follow/2/undo"}', true, 8);

        $this->invalidUndo = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => 'https://example.org/og/b3e4a40b-0b26-4c5a-9079-094bd633fab7',
            'type' => 'Undo',
            'actor' => 'https://example.org/u/alice',
            'object' => [
                'id' => 'https://example.net/u/bob#follows/bb27f601-ddb9-4567-8f16-023d90605ca9',
                'type' => 'Follow',
            ]
        ];
    }

    /** @test */
    public function valid_undo_follow()
    {
        $this->assertTrue(UndoFollow::validate($this->validUndo));
    }

    /** @test */
    public function invalid_undo_follow()
    {
        $this->assertFalse(UndoFollow::validate($this->invalidUndo));
    }
}
