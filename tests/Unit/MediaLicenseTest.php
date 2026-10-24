<?php

namespace Tests\Unit;

use App\Util\Media\License;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MediaLicenseTest extends TestCase
{
    #[Test]
    public function every_license_has_a_uri(): void
    {
        foreach (License::keys() as $id) {
            $this->assertNotNull(License::uriForId($id), "License {$id} has no URI");
        }
    }

    #[Test]
    public function uris_have_no_trailing_slash(): void
    {
        foreach (License::URIS as $uri) {
            $this->assertStringEndsNotWith('/', $uri);
        }
    }

    #[Test]
    public function uri_for_id(): void
    {
        $this->assertSame('https://creativecommons.org/licenses/by/4.0', License::uriForId(11));
        $this->assertSame('https://creativecommons.org/licenses/by-nc-nd/4.0', License::uriForId('16'));
        $this->assertSame('https://creativecommons.org/publicdomain/zero/1.0', License::uriForId(6));
        $this->assertNull(License::uriForId(2));
        $this->assertNull(License::uriForId(null));
        $this->assertNull(License::uriForId('CC BY'));
    }

    #[Test]
    public function id_from_uri(): void
    {
        $this->assertSame(11, License::idFromUri('https://creativecommons.org/licenses/by/4.0'));
        $this->assertSame(1, License::idFromUri('https://rightsstatements.org/vocab/InC/1.0'));
        $this->assertSame(5, License::idFromUri('https://creativecommons.org/publicdomain/mark/1.0'));
    }

    #[Test]
    public function id_from_uri_tolerates_a_trailing_slash(): void
    {
        $this->assertSame(11, License::idFromUri('https://creativecommons.org/licenses/by/4.0/'));
        $this->assertSame(6, License::idFromUri('https://creativecommons.org/publicdomain/zero/1.0/'));
    }

    #[Test]
    public function id_from_uri_requires_the_rest_to_match(): void
    {
        $this->assertNull(License::idFromUri('http://creativecommons.org/licenses/by/4.0'));
        $this->assertNull(License::idFromUri('https://creativecommons.org/licenses/by/4.0/deed.en'));
        $this->assertNull(License::idFromUri('https://creativecommons.org/licenses/by/4.0//'));
        $this->assertNull(License::idFromUri('https://creativecommons.org/licenses/by/3.0'));
        $this->assertNull(License::idFromUri('https://creativecommons.org/licenses/BY/4.0'));
        $this->assertNull(License::idFromUri(['https://creativecommons.org/licenses/by/4.0']));
        $this->assertNull(License::idFromUri(null));
    }

    #[Test]
    public function from_activitypub_uri(): void
    {
        $this->assertSame(12, License::fromActivityPub('https://creativecommons.org/licenses/by-sa/4.0'));
        $this->assertSame(12, License::fromActivityPub('https://creativecommons.org/licenses/by-sa/4.0/'));
    }

    #[Test]
    public function from_activitypub_takes_the_first_known_license_in_a_list(): void
    {
        $this->assertSame(13, License::fromActivityPub([
            'https://example.org/licenses/custom',
            'https://creativecommons.org/licenses/by-nc/4.0',
            'https://creativecommons.org/licenses/by/4.0',
        ]));
    }

    #[Test]
    public function from_activitypub_accepts_a_link_object(): void
    {
        $this->assertSame(11, License::fromActivityPub([
            'type' => 'Link',
            'href' => 'https://creativecommons.org/licenses/by/4.0',
        ]));
    }

    #[Test]
    public function from_activitypub_falls_back_to_the_title(): void
    {
        $this->assertSame(11, License::fromActivityPub('CC BY'));
    }

    #[Test]
    public function from_activitypub_treats_all_rights_reserved_as_unset(): void
    {
        $this->assertNull(License::fromActivityPub('https://rightsstatements.org/vocab/InC/1.0/'));
        $this->assertNull(License::fromActivityPub('All Rights Reserved'));
    }

    #[Test]
    public function from_activitypub_ignores_unknown_values(): void
    {
        $this->assertNull(License::fromActivityPub('https://example.org/licenses/custom'));
        $this->assertNull(License::fromActivityPub(null));
        $this->assertNull(License::fromActivityPub(''));
        $this->assertNull(License::fromActivityPub(['type' => 'Link', 'name' => 'https://creativecommons.org/licenses/by/4.0']));
        $this->assertNull(License::fromActivityPub(11));
    }
}
