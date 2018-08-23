<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class InstalledTest extends TestCase
{
    public function testLandingTest()
    {
        $response = $this->get('/');
        $response
          ->assertStatus(200)
          ->assertSeeText('Image Sharing for Everyone');
    }

    public function testNodeinfoTest()
    {
        $response = $this->get('/.well-known/nodeinfo');
        $response
          ->assertStatus(200)
          ->assertJson([
            "links" => [
              ["rel" => "http://nodeinfo.diaspora.software/ns/schema/2.0"]
          ]]);
    }
}
