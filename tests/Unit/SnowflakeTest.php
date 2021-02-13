<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SnowflakeService;

class Snowflake extends TestCase
{
    /** @test */
    public function snowflakeTest()
    {
    	$expected = 266077397319815168;
    	$actual = SnowflakeService::byDate(now()->parse('2021-02-13T05:36:35+00:00'));
    	$this->assertEquals($expected, $actual);
    }
}
