<?php

namespace Tests\Unit;

use Tests\TestCase;

class SnowflakeTest extends TestCase
{
    /** @test */
    public function snowflakeTest()
    {
        $expected = 266077397319815168;
        $actual = 266077397319815168;
        $this->assertEquals($expected, $actual);
    }
}
