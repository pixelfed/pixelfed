<?php

namespace Tests\Unit;

use App\Util\ActivityPub\Helpers;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CostarTest extends TestCase
{
    /** @test */
    public function blockedDomain()
    {
    	$domains = config('costar.domain.block');
        $this->assertTrue(in_array('example.net', $domains));

        $blockedDomain = 'https://example.org/user/replyGuy';
        $this->assertFalse(Helpers::validateUrl($blockedDomain));

        $unblockedDomain = 'https://pixelfed.org/user/pixelfed';
        $this->assertEquals(Helpers::validateUrl($unblockedDomain), $unblockedDomain);
    }
}
