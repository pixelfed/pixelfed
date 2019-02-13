<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class InstalledTest extends TestCase
{
    /** @test */
    public function landing_page()
    {
        $response = $this->get('/');
        $response->assertSeeText('Image Sharing for Everyone');
    }

    /** @test */
    public function nodeinfo_api()
    {
        $response = $this->get('/.well-known/nodeinfo');
        $response->assertJson([
            'links' => [
              ['rel' => 'http://nodeinfo.diaspora.software/ns/schema/2.0'],
          ], ]);
    }
}
