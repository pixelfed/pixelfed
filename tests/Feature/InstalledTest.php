<?php

namespace Tests\Feature;

use Tests\TestCase;

class InstalledTest extends TestCase
{
    /** @test */
    public function nodeinfo_api(): void
    {
        $response = $this->get('/.well-known/nodeinfo');
        $response->assertJson([
            'links' => [
                ['rel' => 'http://nodeinfo.diaspora.software/ns/schema/2.0'],
            ],
        ]);
    }
}
