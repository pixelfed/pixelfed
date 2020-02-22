<?php

namespace Tests\Unit\ActivityPub\Verb;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Util\ActivityPub\Validator\Announce;

class AnnounceTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();

        $this->validAnnounce = [
            "@context" => "https://www.w3.org/ns/activitystreams",
            "id" => "https://example.org/users/alice/statuses/100000000000001/activity",
            "type" => "Announce",
            "actor" => "https://example.org/users/alice",
            "published" => "2018-12-31T23:59:59Z",
            "to" => [
                "https://www.w3.org/ns/activitystreams#Public"
            ],
            "cc" => [
                "https://example.org/users/bob",
                "https://example.org/users/alice/followers"
            ],
            "object" => "https://example.org/p/bob/100000000000000",
        ];

        $this->invalidAnnounce = [
            "@context" => "https://www.w3.org/ns/activitystreams",
            "id" => "https://example.org/users/alice/statuses/100000000000001/activity",
            "type" => "Announce2",
            "actor" => "https://example.org/users/alice",
            "published" => "2018-12-31T23:59:59Z",
            "to" => [
                "https://www.w3.org/ns/activitystreams#Public"
            ],
            "cc" => [
                "https://example.org/users/bob",
                "https://example.org/users/alice/followers"
            ],
            "object" => "https://example.org/p/bob/100000000000000",
        ];

        $this->invalidDate = [
            "@context" => "https://www.w3.org/ns/activitystreams",
            "id" => "https://example.org/users/alice/statuses/100000000000001/activity",
            "type" => "Announce",
            "actor" => "https://example.org/users/alice",
            "published" => "2018-12-31T23:59:59ZEZE",
            "to" => [
                "https://www.w3.org/ns/activitystreams#Public"
            ],
            "cc" => [
                "https://example.org/users/bob",
                "https://example.org/users/alice/followers"
            ],
            "object" => "https://example.org/p/bob/100000000000000",
        ];

        $this->contextMissing = [
            "id" => "https://example.org/users/alice/statuses/100000000000001/activity",
            "type" => "Announce",
            "actor" => "https://example.org/users/alice",
            "published" => "2018-12-31T23:59:59Z",
            "to" => [
                "https://www.w3.org/ns/activitystreams#Public"
            ],
            "cc" => [
                "https://example.org/users/bob",
                "https://example.org/users/alice/followers"
            ],
            "object" => "https://example.org/p/bob/100000000000000",
        ];

        $this->audienceMissing = [
            "id" => "https://example.org/users/alice/statuses/100000000000001/activity",
            "type" => "Announce",
            "actor" => "https://example.org/users/alice",
            "published" => "2018-12-31T23:59:59Z",
            "object" => "https://example.org/p/bob/100000000000000",
        ];

        $this->audienceMissing2 = [
            "@context" => "https://www.w3.org/ns/activitystreams",
            "id" => "https://example.org/users/alice/statuses/100000000000001/activity",
            "type" => "Announce",
            "actor" => "https://example.org/users/alice",
            "published" => "2018-12-31T23:59:59Z",
            "to" => null,
            "cc" => null,
            "object" => "https://example.org/p/bob/100000000000000",
        ];

        $this->invalidActor = [
            "@context" => "https://www.w3.org/ns/activitystreams",
            "id" => "https://example.org/users/alice/statuses/100000000000001/activity",
            "type" => "Announce",
            "actor" => "10000",
            "published" => "2018-12-31T23:59:59Z",
            "to" => [
                "https://www.w3.org/ns/activitystreams#Public"
            ],
            "cc" => [
                "https://example.org/users/bob",
                "https://example.org/users/alice/followers"
            ],
            "object" => "https://example.org/p/bob/100000000000000",
        ];

        $this->invalidActor2 = [
            "@context" => "https://www.w3.org/ns/activitystreams",
            "id" => "https://example.org/users/alice/statuses/100000000000001/activity",
            "type" => "Announce",
            "published" => "2018-12-31T23:59:59Z",
            "to" => [
                "https://www.w3.org/ns/activitystreams#Public"
            ],
            "cc" => [
                "https://example.org/users/bob",
                "https://example.org/users/alice/followers"
            ],
            "object" => "https://example.org/p/bob/100000000000000",
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
    public function invalid_date()
    {
        $this->assertFalse(Announce::validate($this->invalidDate));
    }

    /** @test */
    public function context_missing()
    {
        $this->assertFalse(Announce::validate($this->contextMissing));
    }

    /** @test */
    public function audience_missing()
    {
        $this->assertFalse(Announce::validate($this->audienceMissing));
        $this->assertFalse(Announce::validate($this->audienceMissing2));
    }

    /** @test */
    public function invalid_actor()
    {
        $this->assertFalse(Announce::validate($this->invalidActor));
        $this->assertFalse(Announce::validate($this->invalidActor2));
    }
}
