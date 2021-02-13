<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DateTimeTest extends TestCase
{
	/** @test */
	public function mastodonTimestamp()
	{
		$ts = Carbon::createFromFormat(\DateTime::ISO8601, '2019-09-16T02:41:57Z');
		$this->assertEquals(9, $ts->month);
		$this->assertEquals(16, $ts->day);
		$this->assertEquals(2019, $ts->year);
		$this->assertEquals(2, $ts->hour);
		$this->assertEquals(41, $ts->minute);
	}

	/** @test */
	public function p3kTimestamp()
	{
		$ts = Carbon::createFromFormat(\DateTime::ISO8601, '2019-09-16T08:40:55+10:00');
		$this->assertEquals(9, $ts->month);
		$this->assertEquals(16, $ts->day);
		$this->assertEquals(2019, $ts->year);
		$this->assertEquals(8, $ts->hour);
		$this->assertEquals(40, $ts->minute);
	}
}
