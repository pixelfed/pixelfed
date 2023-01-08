<?php

namespace Tests\Unit;

use App\Util\Lexer\Nickname;
use Tests\TestCase;

class WebfingerTest extends TestCase
{
    /** @test */
    public function webfingerTest()
    {
        $expected = [
            'domain' => 'pixelfed.org',
            'username' => 'dansup',
        ];
        $actual = Nickname::normalizeProfileUrl('acct:dansup@pixelfed.org');
        $this->assertEquals($expected, $actual);

        $expected = [
            'domain' => 'pixelfed.org',
            'username' => 'dansup_',
        ];
        $actual = Nickname::normalizeProfileUrl('acct:dansup@pixelfed.org');
        $this->assertNotEquals($expected, $actual);

        $expected = [
            'domain' => 'pixelfed.org',
            'username' => 'dansup',
        ];
        $actual = Nickname::normalizeProfileUrl('acct:@dansup@pixelfed.org');
        $this->assertEquals($expected, $actual);

        $expected = [
            'domain' => 'pixelfed.org',
            'username' => 'dansup',
        ];
        $actual = Nickname::normalizeProfileUrl('dansup@pixelfed.org');
        $this->assertEquals($expected, $actual);

        $expected = [
            'domain' => 'pixelfed.org',
            'username' => 'dansup',
        ];
        $actual = Nickname::normalizeProfileUrl('@dansup@pixelfed.org');
        $this->assertEquals($expected, $actual);
    }
}
