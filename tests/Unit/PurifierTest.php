<?php

namespace Tests\Unit;

use Purify;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurifierTest extends TestCase
{
	/** @test */
    public function puckTest()
    {
    	$actual = Purify::clean("<span class=\"fa-spin fa\">catgirl spinning around in the interblag</span>");
    	$expected = 'catgirl spinning around in the interblag';
        $this->assertEquals($expected, $actual);

    	$actual = Purify::clean("<p class=\"fa-spin fa\">catgirl spinning around in the interblag</p>");
    	$expected = '<p>catgirl spinning around in the interblag</p>';
        $this->assertEquals($expected, $actual);
    }
}
